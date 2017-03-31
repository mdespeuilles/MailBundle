<?php
/**
 * Created by PhpStorm.
 * User: maxence
 * Date: 14/03/2017
 * Time: 09:35
 */

namespace Mdespeuilles\MailBundle\Services;

use Symfony\Component\DependencyInjection\ContainerInterface;

class Email {
    
    /**
     * @var ContainerInterface
     */
    protected $container;
    
    public function __construct(ContainerInterface $containerInterface) {
        $this->container = $containerInterface;
    }
    
    public function sendMail($mail, $from, $to, $data) {
        /* @var Email $email */
        $email = $this->container->get('mdespeuilles.entity.email')->findOneBy([
            'machineName' => $mail
        ]);
    
        $body = $email->getBody();
        
        if (isset($data['mail'])) {
            $body = str_replace("[mail]", $data['mail'], $body);
        }
    
        if (isset($data['password'])) {
            $body = str_replace("[password]", $data['password'], $body);
        }
        
        if (isset($data['webform_data'])) {
            $values = "<br />";
            foreach ($data['webform_data'] as $key => $value) {
                $values .= $key . ' : ' . nl2br($value) . '<br />';
            }
    
            $body = str_replace("[webform_data]", $values, $body);
        }
    
    
        $message = \Swift_Message::newInstance()
            ->setFrom($from)
            ->setTo($to)
            ->setSubject($email->getSubject())
            ->setBody($body, 'text/html');
        //->attach(\Swift_Attachment::fromPath($invoiceFile));
        $this->container->get('mailer')->send($message);
    }
}