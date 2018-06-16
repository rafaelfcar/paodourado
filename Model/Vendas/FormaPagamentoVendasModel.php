<?php
include_once("../../Model/BaseModel.php");
include_once("../../Dao/Vendas/FormaPagamentoVendasDao.php");
include_once("../../Model/Nfe/NfeModel.php");
include_once("../../Model/VendaReferencia/VendaReferenciaModel.php");
class FormaPagamentoVendasModel extends BaseModel
{
    function FormaPagamentoVendasModel(){
        If (!isset($_SESSION)){
            ob_start();
            session_start();
        }
    }

    function ListarTipoPagamento($Json=true){
        $dao = new FormaPagamentoVendasDao();
        $lista = $dao->ListarTipoPagamento();
        if ($Json){
            return json_encode($lista);
        }else{
            return $lista;
        }
    }

    function ListarPagamentosVendas(){
        $dao = new FormaPagamentoVendasDao();
        $lista = $dao->ListarPagamentosVendas();
        $total = count($lista);
        $i=0;
        while($i<$total) {
            $lista[$i]['DTA_PAGAMENTO'] = $this->ConverteDataBanco($lista[$i]['DTA_PAGAMENTO']);
            $lista[$i]['VLR_PAGAMENTO'] = number_format($lista[$i]['VLR_PAGAMENTO'],2,'.',',');
            $i++;
        }
        return $lista;
    }
    
    function ListarPagamentosVendasGrid($Json=true){
        $dao = new FormaPagamentoVendasDao();
        $lista = $dao->ListarPagamentosVendas();
        if ($lista[0]){
            $vlrTotal = 0; 
            for ($i=0;$i<count($lista[1]);$i++){
                $vlrTotal = $lista[1][$i]['VLR_PAGAMENTO']+$vlrTotal;
            }
            $lista[2]['VLR_TOTAL'] = number_format($vlrTotal,2,",",".");
        }
        $lista = BaseModel::AtualizaDataInArray($lista, 'DTA_PAGAMENTO');
        $lista = BaseModel::FormataMoedaInArray($lista, 'VLR_PAGAMENTO');
        if ($Json){
            return json_encode($lista);
        }else{
            return $lista;
        }
    }

    function InserirPagamento($Json=true){
        $dao = new FormaPagamentoVendasDao();
        if (filter_input(INPUT_POST, 'nroStatusVenda', FILTER_SANITIZE_STRING) == 'A'){
            $retorno = true;
            $codTipoPagamento = filter_input(INPUT_POST, 'codTipoPagamento', FILTER_SANITIZE_NUMBER_INT);
            $qtdParcelas = filter_input(INPUT_POST, 'qtdParcelas', FILTER_SANITIZE_NUMBER_INT);
            $dtaPagamento = filter_input(INPUT_POST, 'dtaPagamento', FILTER_SANITIZE_STRING);
            if (($codTipoPagamento==3)||($codTipoPagamento==6)){
                $data = $this->makeDate($dtaPagamento, 0, 1);
                $dtaPagamento = $data;
            }
            if ($qtdParcelas>1){
                for($i=0;$i<$qtdParcelas;$i++){
                    $retorno =$dao->InserirPagamento($dtaPagamento);
                    if(!$retorno[0]){
                        exit;
                    }
                    $data = $this->makeDate($dtaPagamento, 0, 1);
                    $dtaPagamento = $data;
                }
            }else{
                $retorno = $dao->InserirPagamento($dtaPagamento);
            }

        }else{
            $retorno[0] = true;
            $retorno[1] = '';
        }
        if ($Json){
            return json_encode($retorno);
        }else{
            return $retorno;
        }
    }

    function DeletarPagamentoVenda($Json=true){
        $dao = new FormaPagamentoVendasDao();
        $lista = $dao->DeletarPagamentoVenda();
        if ($Json){
            return json_encode($lista);
        }else{
            return $lista;
        }
    }

    Public Function FecharVenda(){
        $dao = new FormaPagamentoVendasDao();
        if ($dao->Populate('indEmiteNota')=='S'){
            $produtosVenda = $dao->VerificaProdutosVenda();
//            $servicosVenda = $dao->VerificaProdutosVenda('S');
            $result[0]=true;
            if ($produtosVenda[1][0]['QTD']>0){
                $result = static::EmitirNotaVendaMercadoria();
            }
//            if ($result[0]){
//                if ($servicosVenda[1][0]['QTD']>0){
//                    $result = static::EmitirNotaVendaServico();
//                }
//            }
        }
        if ($result[0] || ($result[2]==400)){
            $result = $dao->FecharVenda($_SESSION['cod_usuario']);
        }
        return json_encode($result);
    }

    Public Static Function EmitirNotaVendaMercadoria(){
        $VendaReferenciaModel = new VendaReferenciaModel();
        $result = $VendaReferenciaModel->RetornaVendaReferencia(false);
        if ($result[0]){                
            if ($result[1][0]['NRO_SEQUENCIAL']>0){
                $nroSequencial = $result[1][0]['NRO_SEQUENCIAL'];
                if ($result[1][0]['IND_STATUS_REFERENCIA']=='A'){
                    $result[0]=false;
                    $result[1]="Esta venda já possui uma Nota Emitida e Autorizada.";
                }else{
                    $result = NfeModel::EmitirNotaMercadoria($nroSequencial);
                    if ($result[0]){
                        $result = $VendaReferenciaModel->UpdateVendaReferencia($nroSequencial, 'A');
                    }
                }
            }else{
                $result = $VendaReferenciaModel->InsertVendaReferencia(false);
                if ($result[0]){
                    $nroSequencial = $result[2];
                    $result = NfeModel::EmitirNotaMercadoria($nroSequencial);
                    if (!$result[0]){
                        $VendaReferenciaModel->UpdateVendaReferencia($nroSequencial, 'E');
                    }
                }
            }
        } 
        return $result;
    }

    Public Static Function EmitirNotaVendaServico(){
        $VendaReferenciaModel = new VendaReferenciaModel();
        $result = $VendaReferenciaModel->RetornaVendaReferencia(false, 'S');
        if ($result[0]){                
            if ($result[1][0]['NRO_SEQUENCIAL']>0){
                $nroSequencial = $result[1][0]['NRO_SEQUENCIAL'];
                if ($result[1][0]['IND_STATUS_REFERENCIA']=='A'){
                    $result[0]=false;
                    $result[1]="Esta venda já possui uma Nota Emitida e Autorizada.";
                }else{
                    $result = NfeModel::EmitirNotaServico($nroSequencial);
                    if ($result[0]){
                        $result = $VendaReferenciaModel->UpdateVendaReferencia($nroSequencial, 'A');
                    }
                }
            }else{
                $referencia = $VendaReferenciaModel->InsertVendaReferencia(false, 'S');
                $nroSequencial = $referencia[2];
                if ($referencia[0]){
                    $result = NfeModel::EmitirNotaServico($nroSequencial);
                    if (!$result[0]){
                        $VendaReferenciaModel->UpdateVendaReferencia($nroSequencial, 'E');
                    }
                }
            }
        }
        return $result;
    }    
    
    Public Function makeDate($date, $days=0, $mounths=0, $years=0){
        $date = explode("/", $date);
        return date('d/m/Y', mktime(0, 0, 0, $date[1] + $mounths, $date[0] +  $days, $date[2] + $years) );
    }

    Public Function VerificaValoresAbaixoMinimo($Json=true){
        $dao = new FormaPagamentoVendasDao();
        $lista = $dao->VerificaValoresAbaixoMinimo($_SESSION['cod_usuario']);
        if ($Json){
            return json_encode($lista);
        }else{
            return $lista;
        }
    }
}
?>
