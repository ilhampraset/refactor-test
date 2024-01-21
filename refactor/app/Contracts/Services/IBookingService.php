<?php

interface IBookingService {
    public function getUserJobs($user_id);
    public function getUsersJobsHistory($user_id, $page);

    public function getAll($request);

    public function feedDistance($data);

    public function cancelJob($data);
}