<?php

class BookingService implements IBookingService {
    private $user;
    private $userRepository;
    private $bookingQueryRepository;

    private $bookingCommandRepository;
    public function __construct(\UserRepository $userRepository, \IBookingQueryRepository $bookingQueryRepository, \IBookingCommandRepository $bookingCommandRepository) {
        $this->userRepository = $userRepository;
        $this->bookingQueryRepository = $bookingQueryRepository;
        $this->bookingCommandRepository=$bookingCommandRepository;
    }
    public function getUserJobs($user_id) {
        $this->user = $this->userRepository->getUserById($user_id);
        $usertype = $this->getUserType();
        $jobs = [];
        if ($usertype=='customer') {
            $jobs = $this->bookingQueryRepository->getCustomerJobs($this->user, ['pending', 'assigned', 'started'], 'due', 'asc', null,0);
        }elseif($usertype=='translator') {
            $jobs = $this->bookingQueryRepository->getTranslatorJobs($user_id, 'new', null);
        }
        $emergencyJobs = $this->filterEmergencyJobs($jobs);
        $normalJobs = $this->collectNormalJobs($jobs, $userId);
        return ['emergencyJobs' => $emergencyJobs, 'normaljobs' => $normalJobs, 'cuser' => $this->user, 'usertype' => $usertype];
    }
    public function getUsersJobsHistory($user_id, $page) {
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

    public function getAll($request){
        return $this->bookingQueryRepository->getAll($request,
            $request->__authenticatedUser->consumer_type,
            $request->limit);
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