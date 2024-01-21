<?php

interface IBookingCommandRepository
{
    public function store($data);
    public function jobEnd();
    public function update($id, $data);
    public function acceptJob($data, $user);

    public function acceptJobWithId($job_id, $cuser);

    public function cancelJobAjax($data, $user);

    public function customerNotCall($post_data);
    public function alerts();

    public function bookingExpireNoAccepted();
    public function reopen($request);

    public function endJob($post_data);
}