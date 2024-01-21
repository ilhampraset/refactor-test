<?php

class CustomerJobFilter {
    use HasCommonJobFilter;


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
        $this->filterTimeType();
        $this->filterJobType();
        $this->filterConsumerType();
        $this->jobs->orderBy('created_at', 'desc')
            ->with('user', 'language', 'feedback.user', 'translatorJobRel.user', 'distance');

        return $this->getResult($limit);
    }

    private function filterId() {
        $this->jobs->when(isset($this->filterData['id']), function ($query) {
            $query->where('id', $this->filterData['id']);
            $this->filterData =  array_only($this->filterData, ['id']);
        });
    }

    private function filterConsumerType() {
        if ($this->filterData['cunsomer_type'] == 'RWS') {
            $this->jobs->where('job_type', '=', 'rws');
        } else {
            $this->jobs->where('job_type', '=', 'unpaid');
        }
    }
}