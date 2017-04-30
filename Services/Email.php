<?php
/**
 * Created by PhpStorm.
 * User: maxence
 * Date: 14/03/2017
 * Time: 09:35
 */

namespace Mdespeuilles\MailBundle\Services;

use PHPHtmlParser\Dom;
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
        
        $body = $this->convertImgSrc($body);
        
        $message = \Swift_Message::newInstance()
            ->setFrom($from)
            ->setTo($to)
            ->setSubject($email->getSubject())
            ->setBody($body, 'text/html');
        //->attach(\Swift_Attachment::fromPath($invoiceFile));
        $this->container->get('mailer')->send($message);
    }
    
    /**
     * Drupal function to check if an url is absolute.
     *
     * @param $url
     * @return bool
     */
    private function is_absolute($url)
    {
        $pattern = "/^(?:ftp|https?|feed):\/\/(?:(?:(?:[\w\.\-\+!$&'\(\)*\+,;=]|%[0-9a-f]{2})+:)*(?:[\w\.\-\+%!$&'\(\)*\+,;=]|%[0-9a-f]{2})+@)?(?:(?:[a-z0-9\-\.]|%[0-9a-f]{2})+|(?:\[(?:[0-9a-f]{0,4}:)*(?:[0-9a-f]{0,4})\]))(?::[0-9]+)?(?:[\/|\?](?:[\w#!:\.\?\+=&@$'~*,;\/\(\)\[\]\-]|%[0-9a-f]{2})*)?$/xi";
        return (bool) preg_match($pattern, $url);
    }
    
    private function convertImgSrc($body) {
        $dom = new Dom();
        $dom->load($body);
        $imgs = $dom->find('img');
    
        $countImg = count($dom->find('img'));
    
        for ($i = 0; $i <= $countImg; $i++) {
            $src = $dom->find('img')[0]->getAttribute('src');
            if (!$this->is_absolute($src)) {
                $schemeAndHttpHost = $this->container->get('request_stack')->getCurrentRequest()->getSchemeAndHttpHost();
            
                $tag = $dom->find('img')[0]->getTag();
                $tag->setAttribute('src', $schemeAndHttpHost . $src);
            }
        }
        
        return $dom;
    }
}