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
    
    public function sendMail($mail, $from, $to, $data, $senderName = null, $replyTo = null) {
        /* @var \Mdespeuilles\MailBundle\Entity\Email $email */
        $email = $this->container->get('mdespeuilles.entity.email')->findOneBy([
            'machineName' => $mail
        ]);
    
        $body = $email->getBody();

        foreach ($data as $key => $value) {
            if ($key != 'webform_data') {
                $body = str_replace("[".$key."]", $value, $body);
            }
        }

        if (isset($data['webform_data'])) {
            $values = "<br />";
            foreach ($data['webform_data'] as $key => $value) {
                $values .= $key . ' : ' . nl2br($value) . '<br />';
            }
    
            $body = str_replace("[webform_data]", $values, $body);
        }
        
        $body = $this->convertImgSrc($body);

        if ($senderName) {
            $from = [
                $from => $senderName
            ];
        }
        
        $message = \Swift_Message::newInstance()
            ->setFrom($from)
            ->setTo($to)
            ->setSubject($email->getSubject())
            ->setBody($body, 'text/html');

        if ($replyTo) {
            $message->setReplyTo($replyTo);
        }

        if (isset($data['attachment'])) {
            $message->attach(\Swift_Attachment::fromPath($data['attachment']));
        }

        if ($email->getAttachmentFile()) {
            $helper = $this->container->get('vich_uploader.templating.helper.uploader_helper');
            $path = $this->container->get('kernel')->getRootDir() . '/../web' . $helper->asset($email, 'attachmentFile');
            $message->attach(\Swift_Attachment::fromPath($path));
        }

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
            if (isset($dom->find('img')[0])) {
                $src = $dom->find('img')[0]->getAttribute('src');
                if (!$this->is_absolute($src)) {
                    if ($this->container->get('request_stack')->getCurrentRequest()) {
                        $schemeAndHttpHost = $this->container->get('request_stack')->getCurrentRequest()->getSchemeAndHttpHost();
                        $tag = $dom->find('img')[0]->getTag();
                        $tag->setAttribute('src', $schemeAndHttpHost . $src);
                    }
                }
            }
        }
        
        return $dom;
    }
}