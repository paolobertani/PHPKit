<?php

require_once ROOT . '/include/3rd-parts/phpmailer/PHPMailer.php';
require_once ROOT . '/include/3rd-parts/phpmailer/SMTP.php';
require_once ROOT . '/include/3rd-parts/phpmailer/Exception.php';

require_once ROOT . '/include/strings.php';
require_once ROOT . '/include/error.php';


// Global

$g_MailerError = '';



//
// Send a email
//
// config array:
//
// $config = [
//     'host' smtp server
//     'auth' authentication true|false
//     'port' port
//     'user' user
//     'pass' password
//     'encr' encryption: '' | 'tls' | 'ssl'
// ];
//
// `to`, `replyTo`, `cc`, `bcc` can be strings or array of strings for multiple adresses
//
// recipient format: `Example <example@example.com>` or simply `example@example.com`
//

function MailerSend( $config, $subject, $body, $altBody, $from, $to, $replyTo = false, $cc = false, $bcc = false )
{
    global $g_MailerError;

    $mailer = new \PHPMailer\PHPMailer\PHPMailer( true );

    $error = '';

    $confParams = [ 'host', 'auth', 'port', 'user', 'pass', 'encr', 'user', 'pass' ];

    foreach( $confParams as $cp )
    {
        if( ! isset( $config[$cp] ) )
        {
            Error( "Missing configuration parameter '$cp'" );
        }
    }

    try
    {
        $mailer->isSMTP(); // Set mailer to use SMTP

        $mailer->SMTPOptions = ['ssl' => [
                                            'verify_peer' => false,
                                            'verify_peer_name' => false,
                                            'allow_self_signed' => true
                                         ]
                               ];

        $mailer->Host       = $config['host'];
        $mailer->SMTPAuth   = $config['auth'];
        $mailer->Port       = $config['port'];
        $mailer->Username   = $config['user'];
        $mailer->Password   = $config['pass'];
        $mailer->SMTPSecure = $config['encr']; // '' | 'tls' | 'ssl'

        $addr = MailerAddress( $from );

        $mailer->From       = $addr['email'];
        $mailer->FromName   = $addr['name'];

        if( substr( $addr['email'], -11, 11 ) === '@pinaxo.com' )
        {
            $pinaxo = $addr['email'];
        }
        else
        {
            $pinaxo = false;
        }

        if( $replyTo !== false )
        {
            $addr = MailerAddress( $replyTo );
            $mailer->addReplyTo( $addr['email'], $addr['email'] );
        }

        if( is_string( $to ) ) { $to = [ $to ]; }

        foreach( $to as $x )
        {
            $addr = MailerAddress( $x );
            if( $addr['name'] === $addr['email'] )
            {
                $mailer->addAddress( $addr['email'] );
            }
            else
            {
                $mailer->addAddress( $addr['email'], $addr['name'] );
            }
        }

        if( $cc !== false )
        {
            if( is_string( $cc ) ) { $cc = [ $cc ]; }

            foreach( $cc as $x )
            {
                $addr = MailerAddress( $x );
                if( $addr['name'] === $addr['email'] )
                {
                    $mailer->addCC( $addr['email'] );
                }
                else
                {
                    $mailer->addCC( $addr['email'], $addr['name'] );
                }
            }
        }

        if( $bcc !== false )
        {
            if( is_string( $bcc ) ) { $bcc = [ $bcc ]; }

            foreach( $bcc as $x )
            {
                $addr = MailerAddress( $x );
                if( $addr['name'] === $addr['email'] )
                {
                    $mailer->addBCC( $addr['email'] );
                }
                else
                {
                    $mailer->addBCC( $addr['email'], $addr['name'] );
                }
            }
        }

        $mailer->CharSet    = 'utf-8';
        $mailer->WordWrap   = 80;
        $mailer->IsHTML( true );

        $mailer->Subject    = $subject;
        $mailer->Body       = $body;
        $mailer->AltBody    = $altBody;

        if( $pinaxo !== false )
        {
            $mailer->DKIM_domain     = 'pinaxo.com';
            $mailer->DKIM_private    = "/Users/administrator/.keys/dkim_rsa";
            $mailer->DKIM_selector   = 'default';
            $mailer->DKIM_passphrase = '';
            $mailer->DKIM_identity   = $pinaxo;
        }

        $mailer->Send();

        return true;
    }
    catch( Exception $e )
    {
        $g_MailerError = $mailer->ErrorInfo;
        return false;
    }
}



function MailerError()
{
    global $g_MailerError;
    return $g_MailerError;
}



//
// Private
//

function MailerAddress( $addr )
{
    $email = StringBetween( $addr, '<', '>' );
    if( $email === false )
    {
        return [ 'name' => $addr, 'email' => $addr ];
    }

    $name = trim( StringReplace( $addr, "<$email>", "" ) );

    return [ 'name' => $name, 'email' => $email ];
}


