<?php

trait HasJobData
{
    public function jobToData($job): array
    {
        $data = [
            'job_id' => $job->id,
            'from_language_id' => $job->from_language_id,
            'immediate' => $job->immediate,
            'duration' => $job->duration,
            'status' => $job->status,
            'gender' => $job->gender,
            'certified' => $job->certified,
            'due' => $job->due,
            'job_type' => $job->job_type,
            'customer_phone_type' => $job->customer_phone_type,
            'customer_physical_type' => $job->customer_physical_type,
            'customer_town' => $job->town,
            'customer_type' => $job->user->userMeta->customer_type,
        ];

        [$due_date, $due_time] = explode(" ", $job->due);
        $data['due_date'] = $due_date;
        $data['due_time'] = $due_time;

        $data['job_for'] = $this->getJobForArray($job);

        return $data;
    }

    private function getJobForArray($job): array
    {
        $jobForArray = [];

        if ($job->gender !== null) {
            $jobForArray[] = ($job->gender == 'male') ? 'Man' : 'Kvinna';
        }

        $certificationMap = [
            'both' => ['Godkänd tolk', 'Auktoriserad'],
            'yes' => ['Auktoriserad'],
            'n_health' => ['Sjukvårdstolk'],
            'law' => ['Rätttstolk'],
            'n_law' => ['Rätttstolk'],
        ];

        $jobForArray = array_merge($jobForArray, $certificationMap[$job->certified] ?? [$job->certified]);
        $jobForArray = array_filter($jobForArray);

        return $jobForArray;
    }
}

