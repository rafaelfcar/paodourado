<?php
include_once("../../Model/BaseModel.php");
include_once("../../Dao/EntradaEstoque/EntradaEstoqueProdutoDao.php");
class EntradaEstoqueProdutoModel extends BaseModel
{
    Public Function EntradaEstoqueProdutoModel(){
        If (!isset($_SESSION)){
            ob_start();
            session_start();
        }
    }

    Public Function ListarDadosProdutosEntrada($Json=true){
        $dao = new EntradaEstoqueProdutoDao();
        $lista = $dao->ListarDadosProdutosEntrada();
        if ($lista[0]){
            $lista = BaseModel::FormataMoedaInArray($lista, "VLR_UNITARIO|VLR_VENDA|VLR_MINIMO");
        }
        if ($Json){
            return json_encode($lista);
        }else{
            return $lista;
        }        
    }

    Public Function InserirProduto($Json=true){
        $dao = new EntradaEstoqueProdutoDao();
        $lista = $dao->InserirProduto();
        if ($Json){
            return json_encode($lista);
        }else{
            return $lista;
        }
    }

    Public Function DeletarProdutoEntrada($Json=true){
        $dao = new EntradaEstoqueProdutoDao();
        $lista = $dao->DeletarProdutoEntrada();
        if ($Json){
            return json_encode($lista);
        }else{
            return $lista;
        }
    }

    Public Function ListarProdutosEntrada(){
        $dao = new EntradaEstoqueProdutoDao();
        $lista = $dao->ListarProdutosEntrada();
        return $lista;
    }

    Public Function BaixaEstoque(){
        $dao = new EntradaEstoqueProdutoDao();
        $lista = $dao->BaixaEstoque();
        return $lista;
    }
}
?>
