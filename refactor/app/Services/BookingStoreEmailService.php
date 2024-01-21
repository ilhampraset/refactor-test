<?php

namespace DTApi\Services;

use Event;
use HasJobData;
use IBookingCommandRepository;
use Job;
use JobWasCreated;
use Mail;
use Services\IBookingStoreService;

class BookingStoreEmailService implements IBookingStoreService
{

    use HasJobData;

    private IBookingCommandRepository $bookingRepository;

    public function __construct(IBookingCommandRepository $bookingRepository)
    {
        $this->bookingRepository = $bookingRepository;
    }

    public function store($data): array
    {
        $userType = $data['user_type'];
        $jobId = $data['user_email_job_id'] ?? 0;
        $job = Job::find($jobId);

        $this->processJobDetails($job, $data);
        $this->bookingRepository->update($jobId, $job);

        $user = $job->user()->first();
        $email = $this->getRecipientEmail($job, $user);
        $name = $user->name;

        $subject = 'Vi har mottagit er tolkbokning. Bokningsnr: #' . $jobId;
        $sendData = ['user' => $user, 'job' => $job];

        Mail::to($email)->send(new \App\Mail\JobCreatedMail($name, $subject, $sendData));

        $response = [
            'type' => $userType,
            'job' => $job,
            'status' => 'success',
        ];

        $eventData = $this->jobToData($job);
        Event::fire(new JobWasCreated($job, $eventData, '*'));

        return $response;
    }

    protected function processJobDetails(Job $job, $data)
    {
        $job->user_email = @$data['user_email'];
        $job->reference = isset($data['reference']) ? $data['reference'] : '';

        if (isset($data['address'])) {
            $user = $job->user()->first();
            $job->address = $data['address'] != '' ? $data['address'] : $user->userMeta->address;
            $job->instructions = $data['instructions'] != '' ? $data['instructions'] : $user->userMeta->instructions;
            $job->town = $data['town'] != '' ? $data['town'] : $user->userMeta->city;
        }
    }

    protected function getRecipientEmail(Job $job, $user)
    {
        return !empty($job->user_email) ? $job->user_email : $user->email;
    }
}