<?php

class BookingCommandRepository extends BaseRepository implements IBookingCommandRepository{

    public function store($data)
    {
        return \User::find($data['user_id'])->jobs()->create($data);
    }

    public function jobEnd()
    {
    }

    public function update($id, $data)
    {
        return \Job::where('id' , $id)->update($data);
    }
}