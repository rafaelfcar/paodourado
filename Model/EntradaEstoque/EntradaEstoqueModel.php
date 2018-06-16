<?php
include_once("../../Model/BaseModel.php");
include_once("../../Dao/EntradaEstoque/EntradaEstoqueDao.php");
class EntradaEstoqueModel extends BaseModel
{
    Public Function EntradaEstoqueModel(){
        If (!isset($_SESSION)){
            ob_start();
            session_start();
        }
    }

    Public Function ListarEntradasEstoqueAberto($Json=true){
        $dao = new EntradaEstoqueDao();
        $lista = $dao->ListarEntradasEstoqueAberto($_SESSION['cod_cliente_final']);
        if ($lista[0]){
            $lista = BaseModel::AtualizaDataInArray($lista, 'DTA_ENTRADA');
            $lista = BaseModel::FormataMoedaInArray($lista, 'VLR_NOTA');
        }
        if ($Json){
            return json_encode($lista);
        }else{
            return $lista;
        }
    }

    Public Function CarregaDadosEntradaEstoque($Json=true){
        $dao = new EntradaEstoqueDao();
        $lista = $dao->CarregaDadosEntradaEstoque($_SESSION['cod_cliente_final']);
        if ($lista[0]){
            $lista = BaseModel::AtualizaDataInArray($lista, 'DTA_ENTRADA');
            $lista = BaseModel::FormataMoedaInArray($lista, 'VLR_NOTA');
        }        
        if ($Json){
            return json_encode($lista);
        }else{
            return $lista;
        }
    }

    Public Function AddEntradaEstoque($Json=true){
        $dao = new EntradaEstoqueDao();
        $result = $dao->AddEntradaEstoque($_SESSION['cod_usuario'], $_SESSION['cod_cliente_final']);
        if ($Json){
            return json_encode($result);
        }else{
            return $result;
        }
    }

    Public Function UpdateEntradaEstoque($Json=true){
        $dao = new EntradaEstoqueDao();
        $result = $dao->UpdateEntradaEstoque();
        if ($Json){
            return json_encode($result);
        }else{
            return $result;
        }        
    }

}
?>