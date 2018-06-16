<?php
include_once("../../Model/BaseModel.php");
include_once("../../Dao/Relatorios/RelatoriosVendasDao.php");
class RelatoriosVendasModel extends BaseModel
{
    Public Function RelatoriosVendasModel(){
        If (!isset($_SESSION)){
            ob_start();
            session_start();
        }
    }

    Public Function DadosVenda(){
        $dao = new RelatoriosVendasDao();
        $dadosVenda = $dao->DadosVenda($_SESSION['cod_cliente_final']);
        $dadosVenda[1][0]['DTA_VENDA'] = $this->ConverteDataBanco($dadosVenda[1][0]['DTA_VENDA']);
        return $dadosVenda;
    }

    Public Function DadosProdutosVenda(){
        $dao = new RelatoriosVendasDao();
        return $dao->DadosProdutosVenda();
    }

    Public Function DadosPagamentosVenda(){
        $dao = new RelatoriosVendasDao();
        $dadosPagamento = $dao->DadosPagamentosVenda();
        $i=0;
        $total = count($dadosPagamento[1]);
        while ($i<$total){
            $dadosPagamento[1][$i]['DTA_PAGAMENTO'] = $this->ConverteDataBanco($dadosPagamento[1][$i]['DTA_PAGAMENTO']);
            $i++;
        }
        return $dadosPagamento;
    }

    Public Function VendasFechadas(){
        $dao = new RelatoriosVendasDao();
        $lista = $dao->VendasFechadas($_SESSION['cod_cliente_final']);
        $lista = $lista[1];
        $total = count($lista);
        $i=0;
        $data = array();
        while($i<$total ) {
            $data[] = array(
                'codVenda' => $lista[$i]['COD_VENDA'],
                'dscCliente' => $lista[$i]['DSC_CLIENTE'],
                'dtaVenda' => $this->ConverteDataBanco($lista[$i]['DTA_VENDA']),
                'vlrTotal' => number_format($lista[$i]['VLR_VENDA'],2),
                'dscVeiculo' => $lista[$i]['DSC_VEICULO'],
                'nmeVendedor' => $lista[$i]['NME_USUARIO_COMPLETO']
            );
            $i++;
        }

        return json_encode($data);
    }

    Public Function PagamentosRecebidos(){
        $dao = new RelatoriosVendasDao();
        $lista = $dao->PagamentosRecebidos($_SESSION['cod_cliente_final']);
        $vlrTotal=0;
        for ($i=0;$i<count($lista[1]);$i++){
            $vlrTotal += $lista[1][$i]['VLR_PAGAMENTO'];
        }
        $lista[1][count($lista[1])]['VLR_TOTAL'] = number_format($vlrTotal,2,",",".");
        $lista = BaseModel::AtualizaDataInArray($lista, 'DTA_PAGAMENTO');
        $lista = BaseModel::FormataMoedaInArray($lista, 'VLR_PAGAMENTO');
        return json_encode($lista);
    }

    Public Function PagamentosRecebidosAtual(){
        $dao = new RelatoriosVendasDao();
        $lista = $dao->PagamentosRecebidosAtual($_SESSION['cod_cliente_final']);
        $lista = BaseModel::AtualizaDataInArray($lista, 'DTA_PAGAMENTO');
        $lista = BaseModel::FormataMoedaInArray($lista, 'VLR_PAGAMENTO');
        return json_encode($lista);
    }

    Public Function VendasAbertas(){
        $dao = new RelatoriosVendasDao();
        $lista = $dao->VendasAbertas($_SESSION['cod_cliente_final']);
        $lista = $lista[1];
        $total = count($lista);
        $i=0;
        $data = array();
        while($i<$total ) {
            $data[] = array(
                'codVenda' => $lista[$i]['COD_VENDA'],
                'dscCliente' => $lista[$i]['DSC_CLIENTE'],
                'dtaVenda' => $this->ConverteDataBanco($lista[$i]['DTA_VENDA']),
                'vlrTotal' => number_format($lista[$i]['VLR_VENDA'],2),
                'dscVeiculo' => $lista[$i]['DSC_VEICULO'],
                'nmeVendedor' => $lista[$i]['NME_USUARIO_COMPLETO']
            );
            $i++;
        }

        return json_encode($data);
    }

    Public Function VendasJustificadas(){
        $dao = new RelatoriosVendasDao();
        $lista = $dao->VendasJustificadas($_SESSION['cod_cliente_final']);
        $total = count($lista);
        $i=0;
        $data = array();
        while($i<$total ) {
            $data[] = array(
                'codVenda' => $lista[$i]['COD_VENDA'],
                'dscCliente' => $lista[$i]['DSC_CLIENTE'],
                'dtaVenda' => $this->ConverteDataBanco($lista[$i]['DTA_VENDA']),
                'vlrTotal' => number_format($lista[$i]['VLR_VENDA'],2),
                'dscVeiculo' => $lista[$i]['DSC_VEICULO'],
                'nmeVendedor' => $lista[$i]['NME_USUARIO_COMPLETO'],
                'txtJustificativa' => $lista[$i]['TXT_JUSTIFICATIVA']
            );
            $i++;
        }

        return json_encode($data);
    }
}
?>
