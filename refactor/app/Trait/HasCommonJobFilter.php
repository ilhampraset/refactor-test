<?php
trait HasCommonJobFilter{

    protected $filterData;
    protected $jobs;

    protected function setFilterData($filterData)
    {
        $this->filterData = $filterData;
    }
    protected function filterFeedback()
    {
        if ($this->requestData['feedback'] ?? 'false' !== 'false') {
            $this->jobs
                ->where('ignore_feedback', '0')
                ->whereHas('feedback', function ($q) {
                    $q->where('rating', '<=', '3');
                });

            if (isset($this->requestData['count']) && $this->requestData['count'] !== 'false') {
                return ['count' => $this->jobs->count()];
            }
        }
    }

    protected function filterLanguage()
    {
        if (isset($this->requestData['lang']) && $this->requestData['lang'] != '') {
            $this->jobs->whereIn('from_language_id', $this->requestData['lang']);
        }
    }

    protected function filterJobType()
    {
        $this->jobs->when(isset($this->requestData['job_type']) && $this->requestData['job_type'] !== '', function ($query) {
            $query->whereIn('job_type', $this->requestData['job_type']);
        });
    }

    protected function filterCustomerEmail()
    {
        $this->jobs->when(
            isset($this->requestData['customer_email']) && count($this->requestData['customer_email']),
            function ($query) {
                $this->applyCustomerEmailFilter($query, $this->requestData['customer_email']);
            }
        );
    }

    protected function filterStatus()
    {
        $this->jobs->when(isset($this->filterData['status']), function ($query) {
            $query->whereIn('status', $this->filterData['status']);
        });
    }

    protected function filterExpiredAt()
    {
        $this->jobs->when(isset($this->filterData['expired_at']), function ($query) {
            $query->where('expired_at', '>=', $this->filterData['expired_at']);
        });
    }

    protected function filterWillExpireAt()
    {
        $this->jobs->when(isset($this->filterData['will_expire_at']), function ($query) {
            $query->where('will_expire_at', '>=', $this->filterData['will_expire_at']);
        });
    }

    protected function filterTimeType()
    {
        $this->jobs->when(isset($this->requestData['filter_timetype']), function ($query) {
            $timetype = $this->requestData['filter_timetype'];
            if (in_array($timetype, ['created', 'due'])) {
                $column = ($timetype == 'created') ? 'created_at' : 'due';

                if (isset($requestdata['from']) && $requestdata['from'] != "") {
                    $query->where($column, '>=', $requestdata['from']);
                }

                if (isset($requestdata['to']) && $requestdata['to'] != "") {
                    $to = $requestdata['to'] . " 23:59:00";
                    $query->where($column, '<=', $to);
                }

                $query->orderBy($column, 'desc');
            }
        });
    }

    protected function getResult($limit)
    {
        if ($limit == 'all') {
            return $this->jobs->get();
        } else {
            return $this->jobs->paginate(15);
        }
    }
}