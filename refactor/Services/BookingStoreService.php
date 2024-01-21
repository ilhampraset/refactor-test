<?php

use Services\IBookingStoreService;

class BookingStoreService implements IBookingStoreService {
    private $userRepository;
    private $bookingCommandRepository;

    public function __construct(\UserRepository $userRepository, \IBookingCommandRepository $bookingCommandRepository)
    {
        $this->userRepository = $userRepository;
        $this->bookingCommandRepository = $bookingCommandRepository;
    }

    public function store($data) {
        $user = $this->userRepository->find($data['user_id']);
        $immediatetime = 5;
        $result = [
            'status' => 'success',
            'type' => ''
        ];

        $consumer_type = $user->userMeta->consumer_type;

        if ($data['immediate'] == 'yes') {
            $due_carbon = Carbon::now()->addMinute($immediatetime);
            $data = $this->processImmediateJob($data, $due_carbon);
        } else {
            $due = $data['due_date'] . " " . $data['due_time'];
            $data = $this->processRegularJob($data, $due);

            if ($data['status'] === 'fail') {
                return $data;
            }
        }

        $data = $this->processAdditionalJobData($data);

        $data['job_type'] = $this->getJobType($consumer_type);

        $data['b_created_at'] = date('Y-m-d H:i:s');

        if (isset($due)) {
            $data['will_expire_at'] = TeHelper::willExpireAt($due, $data['b_created_at']);
        }

        $data['by_admin'] = $data['by_admin'] ?? 'no';

        $job = $this->bookingCommandRepository->store($data);

        if (!$job) {
            $result['status'] = 'fail';
        }

        return $result;
    }

    private function processImmediateJob($data, $due_carbon)
    {
        $data['due'] = $due_carbon->format('Y-m-d H:i:s');
        $data['immediate'] = 'yes';
        $data['customer_phone_type'] = 'yes';

        return $data;
    }

    private function processRegularJob($data, $due)
    {
        $response = ['type' => 'regular'];
        $due_carbon = Carbon::createFromFormat('m/d/Y H:i', $due);
        $data['due'] = $due_carbon->format('Y-m-d H:i:s');

        if ($due_carbon->isPast()) {
            $response['status'] = 'fail';
            $response['message'] = "Can't create booking in the past";
            $data = $response;
        }

        return $data;
    }

    private function processAdditionalJobData($data)
    {
        $genderMap = [
            'male' => 'male',
            'female' => 'female',
        ];

        $certifiedMap = [
            'normal' => 'normal',
            'certified' => 'yes',
            'certified_in_law' => 'law',
            'certified_in_health' => 'health',
        ];
        $jobFor = $data['job_for'];

        $data['gender'] = $this->mapValueFromArray($data['job_for'], $genderMap);
        $data['certified'] = $this->mapValueFromArray($data['job_for'], $certifiedMap);


        if (in_array('normal', $jobFor)) {
            if (in_array('certified', $jobFor)) {
                $data['certified'] = 'both';
            } elseif (in_array('certified_in_law', $jobFor)) {
                $data['certified'] = 'n_law';
            } elseif (in_array('certified_in_health', $jobFor)) {
                $data['certified'] = 'n_health';
            }
        }
        return $data;
    }

    private function mapValueFromArray($sourceArray, $mapArray)
    {
        foreach ($sourceArray as $value) {
            if (isset($mapArray[$value])) {
                return $mapArray[$value];
            }
        }

        return null;
    }


    private function getJobType($consumer_type)
    {
        $typeMapping = [
            'rwsconsumer' => 'rws',
            'ngo' => 'unpaid',
            'paid' => 'paid',
        ];
        return $typeMapping[$consumer_type] ?? '';
    }
}