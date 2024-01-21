<?php

interface INotifier{
    public function send($sender, $receivers, $data, $delay);
}