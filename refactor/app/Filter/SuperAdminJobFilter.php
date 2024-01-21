<?php

class SuperadminJobFilter
{
    use HasCommonJobFilter;

    /**
     * @var mixed
     */

    public function __construct()
    {
        $this->jobs = \Job::query();
    }

    public function applyFilters($filterData, $limit)
    {
        $this->setFilterData($filterData);
        $this->filterFeedback();
        $this->filterId();
        $this->filterLanguage();
        $this->filterStatus();
        $this->filterExpiredAt();
        $this->filterWillExpireAt();
        $this->filterCustomerEmail();
        $this->filterTranslatorEmail();
        $this->filterTimeType();
        $this->filterJobType();
        $this->filterPhysical();
        $this->filterPhone();
        $this->filterFlagged();
        $this->filterEmptyDistance();
        $this->filterSalary();
        $this->filterConsumerType();
        $this->filterBookingType();

        $this->jobs->orderBy('created_at', 'desc')
            ->with('user', 'language', 'feedback.user', 'translatorJobRel.user', 'distance');

        return $this->getResult($limit);
    }


    private function filterId()
    {
        if ($this->filterData['id'] ?? 'false' !== 'false') {
            $idFilter = is_array($this->filterData['id']) ? 'whereIn' : 'where';
            $this->jobs->$idFilter('id', $this->filterData['id']);
            $this->filterData = array_only($this->filterData, ['id']);
        }
    }


    private function filterTranslatorEmail()
    {
        $this->jobs->when(
            isset($this->filterData['translator_email']) && count($this->filterData['translator_email']),
            function ($query) {
                $users = DB::table('users')->whereIn('email', $this->filterData['translator_email'])->get();
                if ($users) {
                    $allJobIDs = DB::table('translator_job_rel')->whereNull('cancel_at')->whereIn('user_id', collect($users)->pluck('id')->all())->lists('job_id');
                    $this->jobs->whereIn('id', $allJobIDs);
                }
            }
        );
    }

    private function filterPhysical()
    {
        $this->jobs->when(isset($this->filterData['physical']), function ($query) {
            $query->where('customer_physical_type', $this->filterData['physical']);
            $query->where('ignore_physical', 0);
        });
    }

    private function filterPhone()
    {
        $this->jobs->when(isset($this->filterData['phone']), function ($query) {
            $query->where('customer_phone_type', $this->filterData['phone']);
            $query->where('ignore_physical_phone', 0);
        });
    }

    private function filterFlagged()
    {
        $this->jobs->when(isset($this->filterData['flagged']), function ($query) {
            $query->where('flagged', $this->filterData['flagged']);
            $query->where('ignore_flagged', 0);
        });
    }

    private function filterEmptyDistance()
    {
        $this->jobs->when(
            isset($this->filterData['distance']) && $this->filterData['distance'] == 'empty',
            function ($query) {
                $query->whereDoesntHave('distance');
            }
        );
    }

    private function filterSalary()
    {
        $this->jobs->when(isset($this->filterData['salary']) && $this->filterData['salary'] == 'yes', function ($query) {
            $query->whereDoesntHave('user.salaries');
        });
    }

    private function filterConsumerType()
    {
        $this->jobs->when(
            isset($this->filterData['consumer_type']) && $this->filterData['consumer_type'] !== '',
            function ($query) {
                $query->where('consumer_type', $this->filterData['consumer_type']);
            }
        );
    }

    private function filterBookingType()
    {
        $this->jobs->when(isset($this->filterData['booking_type']), function ($query) {
            if ($this->filterData['booking_type'] == 'physical'){
                $query->where('customer_physical_type', 'yes');
            }else{
                $query->where('customer_phone_type', 'yes');
            }

        });
    }

}
