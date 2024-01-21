<?php

class BookingPushNotificationService implements IBookingSendNotificationService{

    private  INotifier $notifier;

    public function __construct(INotifier $notifier)
    {
        $this->$notifier = $notifier;
    }

    public function sendNotification($data)
    {
        $users = User::all();
        $receivers = array();
        $receiversNoDelay = array();

        foreach ($users as $oneUser) {
            if ($oneUser->user_type == '2' && $oneUser->status == '1' && $oneUser->id != $exclude_user_id) { // user is translator and he is not disabled
                if (!TeHelper::isNeedToSendPush($oneUser->id)) continue;
                $not_get_emergency = TeHelper::getUsermeta($oneUser->id, 'not_get_emergency');
                if ($data['immediate'] == 'yes' && $not_get_emergency == 'yes') continue;
                $jobs = $this->getPotentialJobIdsWithUserId($oneUser->id); // get all potential jobs of this user
                foreach ($jobs as $oneJob) {
                    if ($job->id == $oneJob->id) { // one potential job is the same with current job
                        $userId = $oneUser->id;
                        $job_for_translator = Job::assignedToPaticularTranslator($userId, $oneJob->id);
                        if ($job_for_translator == 'SpecificJob') {
                            $job_checker = Job::checkParticularJob($userId, $oneJob);
                            if (($job_checker != 'userCanNotAcceptJob')) {
                                if (TeHelper::isNeedToSendPush($oneUser->id)) {
                                    $receivers[] = $oneUser;
                                } else {
                                    $receiversNoDelay[] = $oneUser;
                                }
                            }
                        }
                    }
                }
            }
        }
        $data['language'] = TeHelper::fetchLanguageFromJobId($data['from_language_id']);
        $data['notification_type'] = 'suitable_job';
        $msg_contents = '';
        if ($data['immediate'] == 'no') {
            $msg_contents = 'Ny bokning för ' . $data['language'] . 'tolk ' . $data['duration'] . 'min ' . $data['due'];
        } else {
            $msg_contents = 'Ny akutbokning för ' . $data['language'] . 'tolk ' . $data['duration'] . 'min';
        }

        $data['message'] = array(
            "en" => $msg_contents
        );
        $this->notifier->send($sender, $receiversNoDelay, $data, false);
        $this->notifier->send($sender, $receivers, $data, true);
    }
}