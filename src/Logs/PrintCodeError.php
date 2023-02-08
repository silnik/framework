<?php

namespace Silnik\Logs;

class PrintCodeError
{
    private $title = 'Erro';
    private $text = '';

    public function __construct(
        int $code,
        mixed $log
    ) {
        switch ($code) {
            case 404:
                $this->title = '404 Not Found';
                $this->text = 'A resposta de erro 404 Not Found indica que o servidor não conseguiu encontrar o recurso solicitado.';

                break;
            case 410:
                $this->title = '410 Gone';
                $this->text = 'O código de resposta HTTP 410 Gone de erro do cliente indica que o acesso ao recurso não está mais disponível no servidor de origem, e que esta condição tende a ser permanente.';

                break;
            case 503:
                $this->title = '503 Serviço não Disponível';
                $this->text = 'A mensagem de serviço 503 indisponível significa que o servidor de origem do site não está disponível e geralmente é um estado temporário. Este erro pode ser acionado porque algo em execução no lado do servidor do site travou ou seu site está propositalmente fora do ar para manutenção.';

                break;
            default: break;
        }
        $this->print($log);
        http_response_code($code);
        exit;
    }

    /**
     * Summary of print
     * @param mixed $log
     * @return never
     */
    public function print($log = '')
    {
        $html = '<htm>';
        $html .= '<head>';
        $html .= '   <style>';
        $html .= '       body{ font-family:monospace; text-align:justify }';
        $html .= '       #box{ padding:1rem 2rem; width: 375px; }';
        $html .= '   </style>';
        $html .= '</head>';
        $html .= '<body>';
        $html .= '<div id="box">';
        $html .= '<h1>' . $this->title . '</h1>';
        $html .= '<p>' . $this->text . '</p>';
        if (getenv('APP_ENV') == 'development') {
            $html .= '<p>' . $log . '</p>';
        }
        $html .= '</div>';
        $html .= '</body>';
        $html .= '</html>';
        echo $html;
        exit;
    }
}
