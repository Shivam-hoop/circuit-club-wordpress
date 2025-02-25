<?php 
$email = array(
    'to'        => '', 
    'subject'   => '', 
    'message'   => '{{{content}}}<!-- [IS BUDDYPRESS EMAIL] -->', 
    'headers'   => '', 
    'attachments' => array()
);

$email = Haet_Mail()->style_mail( $email );
echo $email['message'];