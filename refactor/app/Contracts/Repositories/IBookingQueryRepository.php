<?php

interface IBookingQueryRepository
{
    public function getCustomerJobs($user, $statusFilter, $orderField, $sortBy, $paginate, $perPage);

    public function getTranslatorJobs($user_id, $status, $page);

    public function getAll($requestData, $userType, $limit);

    public function getPotentialJobIdsWithUserId($user_id);
    public function getPotentialTranslators(Job $job);
    public function checkParticularJob($user_id, $item);

    public function getJob($id);
}