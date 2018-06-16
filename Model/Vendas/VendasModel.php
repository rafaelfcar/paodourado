<?php
include_once("../../Model/BaseModel.php");
include_once("../../Dao/Vendas/VendasDao.php");
include_once("../../Dao/Vendas/ProdutosVendasDao.php");
include_once("../../Model/Nfe/NfeModel.php");
class VendasModel extends BaseModel
{
    function VendasModel(){
        If (!isset($_SESSION)){
            ob_start();
            session_start();
        }
    }

    function ListarVendasAberto($Json=true){
        $dao = new VendasDao();
        $lista = $dao->ListarVendasAberto($_SESSION['cod_cliente_final']);
        for($i=0;$i<count($lista[1]);$i++){
            $lista[1][$i]['DTA_VENDA'] = BaseModel::ConverteDataBanco($lista[1][$i]['DTA_VENDA']);
            
        }        
        $lista = BaseModel::FormataMoedaInArray($lista, 'VLR_VENDA|VLR_IMPOSTO_PRODUTO|VLR_IMPOSTO_SERVICO');
        if($Json){
            return json_encode($lista);
        }else{
            return $lista;
        }
    }

    function ListarVendasCliente($Json=true){
        $dao = new VendasDao();
        $tpoVenda = filter_input(INPUT_POST, 'tpoVenda', FILTER_SANITIZE_STRING);
        if ($tpoVenda=='A'){
            $tpoVenda = "AND V.NRO_STATUS_VENDA = 'A'";
        }else if ($tpoVenda=='F'){
            $tpoVenda = "AND V.NRO_STATUS_VENDA = 'F'";
        }else{
            $tpoVenda = "";
        }        
        $lista = $dao->ListarVendasCliente($tpoVenda);
        for($i=0;$i<count($lista[1]);$i++){
            $lista[1][$i]['DTA_VENDA'] = BaseModel::ConverteDataBanco($lista[1][$i]['DTA_VENDA']);
        }
        if($Json){
            return json_encode($lista);
        }else{
            return $lista;
        }        
    }

    function VerificaVendasAberto($Json=true){
        $dao = new VendasDao();
        $lista = $dao->VerificaVendasAberto($_SESSION['cod_usuario']);
        if ($lista[1][0]['QTD']>0){
            $retorno = true;
        }else{
            $retorno = false;
        }
        if($Json){
            return json_encode($retorno);
        }else{
            return $retorno;
        }
    }

    function ListarVendas($Json=true){
        $dao = new VendasDao();
        $lista = $dao->ListarVendas($_SESSION['cod_cliente_final']);
        for($i=0;$i<count($lista[1]);$i++){
            $lista[1][$i]['DTA_VENDA'] = BaseModel::ConverteDataBanco($lista[1][$i]['DTA_VENDA']);
        }
        if($Json){
            return json_encode($lista);
        }else{
            return $lista;
        } 
    }

    function CarregaDadosVenda($Json=true){
        $dao = new VendasDao();
        $lista = $dao->CarregaDadosVenda();
        $vlrVendaTotal = '';
        $vlrDescontoTotal = '';
        if ($lista[0]){
            for($i=0;$i<count($lista[1]);$i++){
                $vlrVendaTotal += $lista[1][$i]['VLR_VENDA'];
                $vlrDescontoTotal += $lista[1][$i]['VLR_DESCONTO'];
                $lista[1][$i]['DTA_VENDA'] = BaseModel::ConverteDataBanco($lista[1][$i]['DTA_VENDA']);
                $lista[1][$i]['DTA_EMISSAO_NOTA'] = substr($lista[1][$i]['DTA_EMISSAO_NOTA'], 0, 10).'T'.substr($lista[1][$i]['DTA_EMISSAO_NOTA'], 11, strlen($lista[1][$i]['DTA_EMISSAO_NOTA'])).'-00:00';
            }
            $lista[1][0]['VLR_TOTAL_VENDA'] = $vlrVendaTotal;
            $lista[1][0]['VLR_TOTAL_DESCONTO'] = $vlrDescontoTotal;
            $lista = BaseModel::FormataMoedaInArray($lista, 'VLR_TOTAL_VENDA|VLR_TOTAL_DESCONTO|VLR_VENDA|VLR_DESCONTO|VLR_IMPOSTO_PRODUTO|VLR_IMPOSTO_SERVICO');
        }
        if($Json){
            return json_encode($lista);
        }else{
            return $lista;
        }
    }

    function InsertVenda($Json=true){
        $dao = new VendasDao();
        $lista = $dao->InsertVenda($_SESSION['cod_cliente_final']);
        if($Json){
            return json_encode($lista);
        }else{
            return $lista;
        }        
    }

    function UpdateVenda($Json=true){
        $dao = new VendasDao();
        if (filter_input(INPUT_POST, 'nroStatusVenda', FILTER_SANITIZE_STRING) == 'A'){
            $lista = $dao->UpdateVenda();
        }else{
            $lista[0] = true;
            $lista[1] = '';
        }
        if($Json){
            return json_encode($lista);
        }else{
            return $lista;
        }
    }

    function CancelarVenda(){
        $dao = new VendasDao();
        $PVdao = new ProdutosVendasDao();
        $dao->IniciaTransacao();
        $lista = $PVdao->ListarProdutosVenda();         
        if ($lista[0]){
            for ($i=0;$i<count($lista[1]);$i++){
                if ($lista[1][$i]['IND_ESTOQUE']=="S"){
                    $atualiza = $PVdao->AtualizaEstoque("ADD",
                                          $lista[1][$i]['COD_PRODUTO'],
                                          $lista[1][$i]['NRO_SEQUENCIAL'],
                                          $lista[1][$i]['QTD_VENDIDA']);
                    if (!$atualiza[0]){
                        $i=count($lista[1]);
                        $lista = $atualiza;
                    }
                }
            }
        }
        if ($lista[0]){
            $lista = $dao->CancelarVenda();
        }
        if ($lista[0]){                        
            $dao->ComitaTransacao();
        }else{
            $dao->RolbackTransacao();
        }        
        return json_encode($lista);
    }
    
    function ReabrirVenda(){
        $dao = new VendasDao();
        $nfeModel = new NfeModel();
        $result = $nfeModel->VerificaNota();
        if($result[0]){
            $result = $dao->ReabrirVenda();
        }
        return json_encode($result);
    }

    Public Function ConsolidaOrcamento(){
        $ProdutosVendasForm = new ProdutosVendasForm();
        $dao = new VendasDao();
        $dao->TransformaOrcamentoVenda();
        $ProdutosVendasDao = new ProdutosVendasDao();
        $codigo = $ProdutosVendasForm->getCodProdutoVenda();
        $codigo = str_replace("'", "", $codigo);
        if ($codigo!=''){
            $codigos = explode(",", $codigo);
            if(is_array($codigos)){
                for($i=0;$i<count($codigos);$i++){
                    $cod = explode("S", $codigos[$i]);
                    $ProdutosVendasDao->AtualizaProduto( $ProdutosVendasForm->getCodVenda(),
                                                         $cod[1],
                                                         $cod[0]);
                    $ProdutosVendasDao->AtualizaEstoque("REMOVE", $cod[0], $cod[1], $cod[2]);
                }
            }else{
                $cod = explode("S", $codigo);
                $ProdutosVendasDao->AtualizaProduto( $ProdutosVendasForm->getCodVenda(),
                                                     $cod[1],
                                                     $cod[0]);
                $ProdutosVendasDao->AtualizaEstoque("REMOVE", $cod[0], $cod[1], $cod[2]);
            }
        }
        return true;
    }

}
?>
