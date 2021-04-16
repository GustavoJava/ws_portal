<?php
require_once _MODULEDIR_ . 'Cron/Exception/CronException.php';
require_once _MODULEDIR_ . 'Cron/Action/CronView.php';

/**
 * @CronAction.php
 * 
 * Action padr�o para controladores do Cron
 * 
 * @author 	Alex S. M�dice
 * @email   alex.medice@meta.com.br
 * @version 20/11/2012
 * @since   20/11/2012
 */
class CronAction {
	/**
	 * Nome do processo que est� sendo executado
	 * @var string
	 */
	private $nomeProcesso;

    /**
     * Informa��es do $_REQUEST
     * @property $request
     */
    protected $request;
    
	/**
	 * Link de conex�o com o banco de dados
	 * @var mixed $conn
	 */
	protected $conn;

	/**
	 * A��o atual
	 * @var string $action
	 */
	protected $action;
	
    /**
     * Atributo para acesso a persist�ncia de dados
     * @property FinGeraNfBoletoGraficaDAO
     */
    protected $dao;
    
    /**
     * Informa��es para View
     * @property CronView
     */
    protected $view;
    
    /**
     * @var array $request
     * @return void
     */
    public function __construct($request, $nomeProcesso=null) {
    	
        global $conn;
        
        $this->request 					= $request;
        $this->nomeProcesso 			= $nomeProcesso;
        $this->conn 					= $conn;
        $this->view 					= new CronView();
        $this->action 					= (isset($this->request['acao'])) ? $this->request['acao'] : 'index';

        $this->view->acao  				= $this->action;
        $this->view->msg 				= (isset($this->request['msg'])) ? $this->request['msg'] : '';
        
        if (!empty($this->nomeProcesso)) {
        	$this->verificarProcesso($this->nomeProcesso);
        }
    }
    
    public function setNomeProcesso($nomeProcesso) {
    	$this->nomeProcesso = $nomeProcesso;
    }
    
    /**
     * Verifica se o processo n�o est� travado ou se ainda est� rodando
     *
     * @param string $nomeProcesso
     * @throws CronException
     * @return void
     */
    private function verificarProcesso($nomeProcesso) {
    	if (burnCronProcess($nomeProcesso) === true) {
    		throw new CronException("ERRO: Processo [" . $nomeProcesso . "] ainda est� rodando.");
    	}
    }

    protected function parseSeparatedItem($separator, $item) {
        return array_map('trim', explode($separator,$item));
    }

    protected function dateTimeLog() {
        $date = new DateTime();
        return $date->format("Y/m/d H:i:s");
    }

    protected function log($type,$msg) {        
        echo "{$this->dateTimeLog()} [{$type}]- {$msg}\n";
    }
}