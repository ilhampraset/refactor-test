<?php

namespace DTApi\Http\Controllers;

use DTApi\Http\Requests;
use DTApi\Models\Distance;
use DTApi\Models\Job;
use DTApi\Repository\BookingRepository;
use DTApi\Services\BookingService;
use Illuminate\Http\Request;

/**
 * Class BookingController
 * @package DTApi\Http\Controllers
 */
class BookingController extends Controller
{


    protected $bookingService;

    /**
     * BookingController constructor.
     * @param BookingRepository $bookingRepository
     */
    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $authenticatedUser = $request->__authenticatedUser;
        if ($authenticatedUser->user_type == config('constants.roles.ADMIN_ROLE_ID') || $authenticatedUser->user_type == config('constants.roles.SUPERADMIN_ROLE_ID')) {
            $response = $this->bookingService->getAll($request);
        }
        return response($response);
    }

    public function getByIdUser(Request $request, $user_id) {
        return $this->bookingService->getUserJobs($user_id);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function show($id)
    {
        $job = $this->bookingService->getJobs($id);

        return response($job);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function store(Request $request)
    {
        $request = $request->all();
        $response = [
            'status' => 'success',
            'message' => '',
            'type' => '',
        ];
        try {
            if ($request->user_type == config('constants.roles.CUSTOMER_ROLE_ID')) {
                FieldValidator::validateField($data, 'from_language_id', 'Du måste fylla in alla fält');

                if ($data['immediate'] == 'no') {
                    FieldValidator::validateField($data, 'due_date', 'Du måste fylla in alla fält');
                    FieldValidator::validateField($data, 'due_time', 'Du måste fylla in alla fält');
                    if (!isset($data['customer_phone_type']) && !isset($data['customer_physical_type'])) {
                        throw new ValidationException('customer_phone_type', 'Du måste göra ett val här');
                    }
                }
                FieldValidator::validateField($data, 'duration', 'Du måste fylla in alla fält');

                $store = app(\Services\IBookingStoreService::class,
                    [config('strategies.store_context.storeBooking')]
                )->store($data);

                if(!empty($store)) {
                    $response['status'] = 'success';
                    $response['message'] = 'success created data';
                    $response['type'] =$store['type'];
                }else{
                    throw new ValidationResource([
                        'status' => $response['status'],
                        'message' => $response['message'],
                        'type' => '',
                    ]);
                }
            }
            else {
                throw new ValidationException('', 'Translator can not create booking');
            }

        } catch (ValidationException $exception) {
            return new ValidationResource([
                'status' => 'fail',
                'message' => $exception->getMessage(),
                'field_name' => $exception->field,
            ]);
        }

        return response($response);

    }

    /**
     * @param $id
     * @param Request $request
     * @return mixed
     */
    public function update($id, Request $request)
    {
        $data = $request->all();
        $cuser = $request->__authenticatedUser;
        $response = $this->bookingService->updateJob($id, array_except($data, ['_token', 'submit']), $cuser);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function immediateJobEmail(Request $request)
    {
        $data = $request->all();
        $response = app(\Services\IBookingStoreService::class,
            [config('strategies.store_context.storeEmailBooking')]
        )->store($data);
        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getHistory(Request $request)
    {
        if($user_id = $request->get('user_id')) {

            $response = $this->repository->getUsersJobsHistory($user_id, $request);
            return response($response);
        }

        return null;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function acceptJob(Request $request)
    {
        $data = $request->all();
        $user = $request->__authenticatedUser;

        $response = $this->bookingService->acceptJob($data, $user);

        return response($response);
    }

    public function acceptJobWithId(Request $request)
    {
        $data = $request->get('job_id');
        $user = $request->__authenticatedUser;

        $response = $this->bookingService->acceptJob($data, $user);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function cancelJob(Request $request)
    {
        $data = $request->all();
        $user = $request->__authenticatedUser;

        $response = $this->bookingService->cancelJob($data, $user);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function endJob(Request $request)
    {
        $data = $request->all();

        $response = $this->bookingService->endJob($data);

        return response($response);

    }

    public function customerNotCall(Request $request)
    {
        $data = $request->all();

        $response = $this->bookingService->customerNotCall($data);

        return response($response);

    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getPotentialJobs(Request $request)
    {
        $data = $request->all();
        $user = $request->__authenticatedUser;

        $response = $this->bookingService->getPotentialJobs($user);

        return response($response);
    }

    public function distanceFeed(Request $request)
    {
        $data = $request->all();
        return response($this->bookingServicefeedDistance($data));
    }

    public function reopen(Request $request)
    {
        $data = $request->all();
        $response = $this->bookingService->reopen($data);

        return response($response);
    }

    public function resendNotifications(Request $request)
    {
        $data = $request->all();
        $service = new \DTApi\Services\BookingPushNotificationService(new \PushNotification());
        $service->sendNotification($data);
        return response(['success' => 'Push sent']);    }

    /**
     * Sends SMS to Translator
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function resendSMSNotifications(Request $request)
    {
        $service = new \DTApi\Services\BookingSmsNotificationService(new \SmsNotification());
        try {
            $service->sendNotification($job);
            return response(['success' => 'SMS sent']);
        } catch (\Exception $e) {
            return response(['success' => $e->getMessage()]);
        }
    }

}
