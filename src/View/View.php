<?php

namespace Anticus\View;
use Twig\Extra\Html\HtmlExtension;
use Anticus\Twig\ReadingTimeExtension;
use Anticus\Twig\TitleCaseExtension;

/**
 * View
 */
class View
{

    /**
     * Render a PHP template
     *
     * @param string $template The template file path
     * @param array<mixed> $data config and data to display in the view
     * @return void
     */
    public static function render(string $template, array $data = [])
    {
        if ( isset($data['config']['paths']['Template']) ) {
            $file = $data['config']['paths']['Template'] . DS . $template;
        } else {
            throw new \Exception("config not found");
        }

        if (is_readable($file)) {
            require $file;
        } else {
            throw new \Exception("$file not found");
        }
    }
    
    /**
     * Render a view template using Twig
     *
     * @param string $template  The template file
     * @param array<mixed> $data  config and data to display in the view
     *
     * @return void
     */
    public static function renderTemplate(string $template, array $data = [])
    {
        static $twig = null;

        if ($twig === null) {
            $loader = new \Twig\Loader\FilesystemLoader($data['config']['paths']['Template']);
            if ($data['config']['debug'] === true) {
                $twig = new \Twig\Environment($loader, ['debug' => true]);
                $twig->addExtension(new \Twig\Extension\DebugExtension());
            } else {
                $twig = new \Twig\Environment($loader, [
                    'cache' => $data['config']['paths']['Cache'] . DS . 'twig'
                ]);
            }
            // CSRF protection, https://stackoverflow.com/a/31683058/354196
            $twig->addFunction(
                new \Twig\TwigFunction(
                    'form_token',
                    function($lock_to = null) {
                        if (empty($_SESSION['token'])) {
                            $_SESSION['token'] = bin2hex(random_bytes(32));
                        }
                        if (empty($_SESSION['token2'])) {
                            $_SESSION['token2'] = random_bytes(32);
                        }
                        if (empty($lock_to)) {
                            return $_SESSION['token'];
                        }
                        
                        return hash_hmac('sha256', $lock_to, $_SESSION['token2']);
                    }
                )
            );
            $twig->addExtension(new HtmlExtension());
            $twig->addExtension(new ReadingTimeExtension());
            $twig->addExtension(new TitleCaseExtension());
        }
        echo $twig->render($template, $data);
    }
}
