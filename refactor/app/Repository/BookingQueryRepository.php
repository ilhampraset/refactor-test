<?php

class BookingQueryRepository extends BaseRepository implements IBookingQueryRepository{

    public function getCustomerJobs(User $user, $statusFilter=[], $orderField, $sortBy, $paginate = false, $perPage = 15)
    {
        $query = $user->jobs()
            ->with('user.userMeta', 'user.average', 'translatorJobRel.user.average', 'language', 'feedback', 'distance')
            ->whereIn('status', $statusFilter)
            ->orderBy($orderField, $sortBy);

        return $paginate ? $query->paginate($perPage, ['*'], 'page', request('page')) : $query->get();
    }

    public function getTranslatorJobs($user_id, $status='new', $page=15) {

        if ($status == 'historic') {
            return Job::getTranslatorJobsHistoric($user_id, 'historic', $page);
        }
        return Job::getTranslatorJobs($user_id)->pluck('jobs')->all();
    }

    public function getAll($requestData, $userType, $limit)
    {
        $roleType = [
            config('constans.roles.SUPERADMIN_ROLE_ID') => SuperadminJobFilter::class,
            config('constans.roles.CUSTOMER_ROLE_ID') => CustomerJobFilter::class,
        ];
        return (new $roleType[$userType])->applyFilters($requestData, $limit);
    }

    public function getPotentialJobIdsWithUserId($user_id)
    {
        $user_meta = UserMeta::where('user_id', $user_id)->first();
        $translator_type = $user_meta->translator_type;
        $job_type = 'unpaid';
        if ($translator_type == 'professional')
            $job_type = 'paid';   /*show all jobs for professionals.*/
        else if ($translator_type == 'rwstranslator')
            $job_type = 'rws';  /* for rwstranslator only show rws jobs. */
        else if ($translator_type == 'volunteer')
            $job_type = 'unpaid';  /* for volunteers only show unpaid jobs. */

        $languages = UserLanguages::where('user_id', '=', $user_id)->get();
        $userlanguage = collect($languages)->pluck('lang_id')->all();
        $gender = $user_meta->gender;
        $translator_level = $user_meta->translator_level;
        $job_ids = Job::getJobs($user_id, $job_type, 'pending', $userlanguage, $gender, $translator_level);
        foreach ($job_ids as $k => $v)     // checking translator town
        {
            $job = Job::find($v->id);
            $jobuserid = $job->user_id;
            $checktown = Job::checkTowns($jobuserid, $user_id);
            if (($job->customer_phone_type == 'no' || $job->customer_phone_type == '') && $job->customer_physical_type == 'yes' && $checktown == false) {
                unset($job_ids[$k]);
            }
        }
        $jobs = TeHelper::convertJobIdsInObjs($job_ids);
        return $jobs;
    }

    public function getPotentialTranslators(Job $job)
    {

        $job_type = $job->job_type;

        if ($job_type == 'paid')
            $translator_type = 'professional';
        else if ($job_type == 'rws')
            $translator_type = 'rwstranslator';
        else if ($job_type == 'unpaid')
            $translator_type = 'volunteer';

        $joblanguage = $job->from_language_id;
        $gender = $job->gender;
        $translator_level = [];
        if (!empty($job->certified)) {
            if ($job->certified == 'yes' || $job->certified == 'both') {
                $translator_level[] = 'Certified';
                $translator_level[] = 'Certified with specialisation in law';
                $translator_level[] = 'Certified with specialisation in health care';
            }
            elseif($job->certified == 'law' || $job->certified == 'n_law')
            {
                $translator_level[] = 'Certified with specialisation in law';
            }
            elseif($job->certified == 'health' || $job->certified == 'n_health')
            {
                $translator_level[] = 'Certified with specialisation in health care';
            }
            else if ($job->certified == 'normal' || $job->certified == 'both') {
                $translator_level[] = 'Layman';
                $translator_level[] = 'Read Translation courses';
            }
            elseif ($job->certified == null) {
                $translator_level[] = 'Certified';
                $translator_level[] = 'Certified with specialisation in law';
                $translator_level[] = 'Certified with specialisation in health care';
                $translator_level[] = 'Layman';
                $translator_level[] = 'Read Translation courses';
            }
        }

        $blacklist = UsersBlacklist::where('user_id', $job->user_id)->get();
        $translatorsId = collect($blacklist)->pluck('translator_id')->all();
        $users = User::getPotentialUsers($translator_type, $joblanguage, $gender, $translator_level, $translatorsId);

        return $users;

    }

    public function getJob($id) {
       return $this->bookingQueryRepository->find($id);
    }
    public function checkParticularJob($user_id, $item) {
        return \Job::checkParticularJob($user_id, $item);
    }



}