<?php
include_once("../BaseController.php"); 
include_once("../../Model/Nfe/NfeModel.php");
class NfeController extends BaseController
{
    Public Function NfeController(){
        eval("\$this->".BaseController::getMethod()."();");
    }
    
    Public Function ConsultarNota(){
        $nfeModel = new NfeModel();
        echo $nfeModel->ConsultarNota();
    }
    
    Public Function CancelarNota(){        
        $nfe = new NfeModel();
        echo $nfe->CancelarNota();
    }
    
    
}
$classController = new NfeController();