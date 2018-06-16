<?php
include_once("../BaseController.php");
include_once("../../Model/EntradaEstoque/EntradaEstoqueModel.php");
class EntradaEstoqueController extends BaseController
{
    Public Function EntradaEstoqueController(){
        eval("\$this->".BaseController::getMethod()."();");
    }

    Public Function ChamaView(){
        $view = $this->getPath()."/View/EntradaEstoque/".str_replace("Controller", "View", get_class($this)).".php";
        header("Location: ".$view);
    }

    Public Function ListarEntradasEstoqueAberto(){
        $model = new EntradaEstoqueModel();
        echo $model->ListarEntradasEstoqueAberto();
    }

    Public Function CarregaDadosEntradaEstoque(){
        $model = new EntradaEstoqueModel();
        echo $model->CarregaDadosEntradaEstoque();
    }
  
    Public Function AddEntradaEstoque(){
        $model = new EntradaEstoqueModel();
        echo $model->AddEntradaEstoque(); 
    }
  
    Public Function UpdateEntradaEstoque(){
        $model = new EntradaEstoqueModel();
        echo $model->UpdateEntradaEstoque();
    }

}
$entradaEstoqueController = new EntradaEstoqueController();
?>