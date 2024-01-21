<?php

interface IBookingCommandRepository
{
    public function store($data);
    public function jobEnd();
    public function update($id, $data);
}