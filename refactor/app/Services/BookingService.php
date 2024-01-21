<?php

namespace DTApi\Services;
use IBookingService;

class BookingService implements IBookingService
{
    private $user;
    private $userRepository;
    private $bookingQueryRepository;

    private $bookingCommandRepository;

    public function __construct(\UserRepository $userRepository, \IBookingQueryRepository $bookingQueryRepository, \IBookingCommandRepository $bookingCommandRepository)
    {
        $this->userRepository = $userRepository;
        $this->bookingQueryRepository = $bookingQueryRepository;
        $this->bookingCommandRepository = $bookingCommandRepository;
    }

    public function getUserJobs($user_id)
    {
        $this->user = $this->userRepository->getUserById($user_id);
        $usertype = $this->getUserType();
        $jobs = [];
        if ($usertype == 'customer') {
            $jobs = $this->bookingQueryRepository->getCustomerJobs($this->user, ['pending', 'assigned', 'started'], 'due', 'asc', null, 0);
        } elseif ($usertype == 'translator') {
            $jobs = $this->bookingQueryRepository->getTranslatorJobs($user_id, 'new', null);
        }
        $emergencyJobs = $this->filterEmergencyJobs($jobs);
        $normalJobs = $this->collectNormalJobs($jobs, $userId);
        return ['emergencyJobs' => $emergencyJobs, 'normaljobs' => $normalJobs, 'cuser' => $this->user, 'usertype' => $usertype];
    }

    public function getUsersJobsHistory($user_id, $page)
    {
        $this->user = $this->userRepository->getUserById($user_id);
        $usertype = $this->getUserType();
        if ($usertype == 'translator') {
            $jobs = $this->bookingQueryRepository->getTranslatorJobs($user_id, 'historic', $page);
            $totalJobs = $jobs->total();
            $numPages = ceil($totalJobs / 15);
            return [
                'emergencyJobs' => [],
                'normalJobs' => $jobs,
                'jobs' => $jobs,
                'user' => $this->user,
                'usertype' => 'translator',
                'numPages' => $numPages,
                'pageNum' => $page,
            ];
        }
        return [
            'emergencyJobs' => [],
            'normalJobs' => $this->bookingQueryRepository->getCustomerJobs($this->user, ['completed', 'withdrawbefore24', 'withdrawafter24', 'timedout'], 'due', 'asc', true, 15),
            'user' => $this->user,
            'usertype' => 'customer',
            'numPages' => 0,
            'pageNum' => 0,
        ];

    }

    public function getAll($request)
    {
        return $this->bookingQueryRepository->getAll($request,
            $request->__authenticatedUser->consumer_type,
            $request->limit);
    }

    public function feedDistance($data)
    {
        if (isset($data['distance']) && $data['distance'] != "") {
            $distance = $data['distance'];
        } else {
            $distance = "";
        }
        if (isset($data['time']) && $data['time'] != "") {
            $time = $data['time'];
        } else {
            $time = "";
        }
        if (isset($data['jobid']) && $data['jobid'] != "") {
            $jobid = $data['jobid'];
        }

        if (isset($data['session_time']) && $data['session_time'] != "") {
            $session = $data['session_time'];
        } else {
            $session = "";
        }

        if ($data['flagged'] == 'true') {
            if($data['admincomment'] == '') return "Please, add comment";
            $flagged = 'yes';
        } else {
            $flagged = 'no';
        }

        if ($data['manually_handled'] == 'true') {
            $manually_handled = 'yes';
        } else {
            $manually_handled = 'no';
        }

        if ($data['by_admin'] == 'true') {
            $by_admin = 'yes';
        } else {
            $by_admin = 'no';
        }

        if (isset($data['admincomment']) && $data['admincomment'] != "") {
            $admincomment = $data['admincomment'];
        } else {
            $admincomment = "";
        }
        if ($time || $distance) {

            $affectedRows = Distance::where('job_id', '=', $jobid)->update(array('distance' => $distance, 'time' => $time));
        }

        if ($admincomment || $session || $flagged || $manually_handled || $by_admin) {

            $affectedRows1 = Job::where('id', '=', $jobid)->update(array('admin_comments' => $admincomment, 'flagged' => $flagged, 'session_time' => $session, 'manually_handled' => $manually_handled, 'by_admin' => $by_admin));

        }
        if ($affectedRows || $affectedRows1) {
            return 'Record Updated!';
        }
       return 'Failed Updated!';;

    }

    public function acceptJob($data, $user_id) {

        $response = [];
        $user = $this->userRepository->find($user_id);
        $job_id = $data->job_id ?? 0;
        if ($job_id > 0) {
            $response = $this->bookingCommandRepository->acceptJobWithId($job_id, $user);
        }else{
            $response = $this->bookingCommandRepository->acceptJob();
        }

        return $response;
    }

    public function cancelJob($data) {
        return $this->bookingCommandRepository->cancelJobAjax($data, $user);
    }

    private function getUserType()
    {
        return $this->user->is('customer') ? 'customer' : ($this->user->is('translator') ? 'translator' : '');
    }

    private function filterEmergencyJobs($jobs)
    {
        return array_filter($jobs, function ($jobitem) {
            return $jobitem->immediate == 'yes';
        });
    }

    private function collectNormalJobs($jobs, $userId)
    {
        return collect($jobs)->each(function ($item, $key) use ($userId) {
            $item['usercheck'] = $this->bookingQueryRepository->checkParticularJob($userId, $item);
        })->sortBy('due')->all();
    }


}