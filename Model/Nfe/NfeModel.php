<?php
include_once("../../Model/BaseModel.php");
include_once("../../Dao/Nfe/NfeDao.php");
include_once("../../Model/Vendas/VendasModel.php");
include_once("../../Model/Vendas/ProdutosVendasModel.php");
include_once("../../Model/VendaReferencia/VendaReferenciaModel.php");
class NfeModel extends BaseModel
{
    public function NfeModel(){
        If (!isset($_SESSION)){
            ob_start();
            session_start();
        }
    }
    
    Public Static Function EmitirNotaMercadoria($referencia){
        $server = URL;
        $token= TOKEN;
        $ref = filter_input(INPUT_POST, 'codVenda', FILTER_SANITIZE_NUMBER_INT)."00".$referencia;
        $VendaModel = new VendasModel();
        $dadosVenda = $VendaModel->CarregaDadosVenda(FALSE);
        if (AMBIENTE=='HMG'){
            $destinatario = "NF-E EMITIDA EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL";
        }else{
            $destinatario = $dadosVenda[1][0]['DSC_CLIENTE'];
        }
            
        $nfe = array (
            "natureza_operacao" => "Remessa de Produtos",
            "forma_pagamento" => "0",
            "data_emissao" => $dadosVenda[1][0]['DTA_EMISSAO_NOTA'],
            "tipo_documento" => "1",
            "finalidade_emissao" => "1",
            "cnpj_emitente" => "26441410000161",
            "inscricao_estadual_emitente" => "767247800177",
            "nome_destinatario" => $destinatario,
            "cpf_destinatario" => $dadosVenda[1][0]['NRO_CPF'],
            "logradouro_destinatario" => $dadosVenda[1][0]['TXT_LOGRADOURO'],
            "numero_destinatario" => $dadosVenda[1][0]['TXT_COMPLEMENTO'],
            "bairro_destinatario" => $dadosVenda[1][0]['NME_BAIRRO'],
            "municipio_destinatario" => $dadosVenda[1][0]['TXT_LOCALIDADE'],
            "uf_destinatario" => $dadosVenda[1][0]['SGL_UF'],
            "pais_destinatario" => "Brasil",
            "cep_destinatario" => $dadosVenda[1][0]['NRO_CEP'],
            "icms_base_calculo" => "0",
            "icms_valor_total" => "0",
            "icms_base_calculo_st" => "0",
            "icms_valor_total_st" => "0",
            "icms_modalidade_base_calculo" => "0",
            "icms_valor" => "0",
            "valor_frete" => "0.0000",
            "valor_seguro" => "0",
            "valor_total" => str_replace(',', '.', str_replace('.', '', $dadosVenda[1][0]['VLR_TOTAL_VENDA'])),
            "valor_produtos" => str_replace(',', '.', str_replace('.', '', $dadosVenda[1][0]['VLR_VENDA'])),
            "valor_ipi" => "0",
            "modalidade_frete" => "0",
            "informacoes_adicionais_contribuinte" => "Não Incidência ICMS conforme Decisão...",
            "items" => static::RetornaProdutosNfe(),
            "duplicatas" => array(
                array(
                    "numero" => "Pagamento a vista",
                    "valor" => str_replace(',', '.', str_replace('.', '', $dadosVenda[1][0]['VLR_TOTAL_VENDA']))
                )
            ),
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $server."/nfe2/autorizar.json?ref=" . $ref . "&token=" . $token);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($nfe));
        $body = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $body = json_decode($body); 
        curl_close($ch); 
        if ($http_code!=202){
            $result[0] = false;
            $result[1] = "Foram encontrados ".count($body->erros)." erros, listados abaixo!";
            for ($i=0;$i<count($body->erros);$i++){
                $result[2][]=$i+1 . " - ".$body->erros[$i]->mensagem."<BR>";
            }
        }else{
            $result[0] = true;
        }
        return $result;
    }
    
    Public Static Function RetornaProdutosNfe(){
        $NfeDao = new NfeDao();
        $produtosVendas = $NfeDao->RetornaMercadoriasVenda();
        for ($i=0;$i<count($produtosVendas[1]);$i++){
            $item = $i+1;
            if ($produtosVendas[1][$i]['TPO_PRODUTO']=='S'){
                $tpoUnidade = "SV";
            }else{
                $tpoUnidade = "UN";
            }
            $vlrSoma = ($produtosVendas[1][$i]['VLR_VENDA']-$produtosVendas[1][$i]['VLR_DESCONTO'])*$produtosVendas[1][$i]['QTD_VENDIDA'];
            $produtos[$i] = array("numero_item" => $item.'',
                                "codigo_produto" => $produtosVendas[1][$i]['COD_PRODUTO'],
                                "descricao" => $produtosVendas[1][$i]['DSC_PRODUTO'],
                                "cfop" => $produtosVendas[1][$i]['COD_CFOP'],
                                "unidade_comercial" => $tpoUnidade,
                                "quantidade_comercial" => $produtosVendas[1][$i]['QTD_VENDIDA'],
                                "valor_unitario_comercial" => number_format($produtosVendas[1][$i]['VLR_VENDA'],2,'.',''),
                                "valor_unitario_tributavel" => number_format($produtosVendas[1][$i]['VLR_VENDA'],2,'.',''),
                                "unidade_tributavel" => "un",
                                "codigo_ncm" => $produtosVendas[1][$i]['COD_NCM'],
                                "quantidade_tributavel" => $produtosVendas[1][$i]['QTD_VENDIDA'],
                                "valor_bruto" => number_format($vlrSoma,2,'.',''),
                                "icms_situacao_tributaria" => $produtosVendas[1][$i]['DSC_CODIGO_ICMS'],
                                "icms_origem" => $produtosVendas[1][$i]['COD_ICMS_ORIGEM'],
                                "pis_situacao_tributaria" => $produtosVendas[1][$i]['DSC_CODIGO_PIS'],
                                "cofins_situacao_tributaria" => $produtosVendas[1][$i]['DSC_CODIGO_COFINS']);                
                                
        } 
//        var_dump($produtos); die;
        return $produtos;
    }
    
    Public Function VerificaNota(){
        $VendaReferenciaModel = new VendaReferenciaModel();
        $result = $VendaReferenciaModel->RetornaVendaReferencia(false);
        if ($result[0]){                
            if ($result[1][0]['NRO_SEQUENCIAL']>0){
                if ($result[1][0]['IND_STATUS_REFERENCIA']=='A'){
                    $dataEmissao = $result[1][0]['DTA_EMISSAO_NOTA'];
                    $codVenda = $result[1][0]['COD_VENDA'];
                    $difHoras = BaseModel::diffDate($dataEmissao, date('Y-m-d H:i:s'));
                    if ($difHoras>24){
                        $result[0]=false;
                        $result[1]="Prazo para cancelamento expirou!";
                    }else{
                        $result[0]=false;
                        $result[1]='Venda com Nota Fiscal de Produtos Emitida para a Receita e aprovada!<br>
                                    Deseja Cancelar a nota anterior?<br>
                                    <input type="button" value="Sim" onclick="javascript:CancelarNota('.$codVenda.');">
                                    <input type="button" value="Não" onclick="javascript:FecharDialog();">';
                    }
                }else{
                    $result[0]=true;
                }
            }else{
                $result[0]=true;
            }
        }        
        return $result;
    }
    
    Public Function ConsultarNota(){
        $result = $this->ConsultarNotaMercadoria();
        //$result['servico'] = $this->ConsultarNotaServico();
        echo json_encode($result);
    }

    Public Function ConsultarNotaServico(){
        $server = URL;
        $token= TOKEN;
        $VendaReferenciaModel = new VendaReferenciaModel();
        $result = $VendaReferenciaModel->RetornaVendaReferencia(false,'S');
        $ref = filter_input(INPUT_POST, 'codVenda', FILTER_SANITIZE_NUMBER_INT)."00".$result[1][0]['NRO_SEQUENCIAL'];   
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $server."/nfse/" . $ref . ".json?token=" . $token);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array());
        $body = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch); 
        $body = json_decode($body);
//        var_dump($body); die;
        if ($body->status=="erro_autorizacao"){
            if (property_exists($body, 'mensagem_sefaz')){
                $VendaReferenciaModel = new VendaReferenciaModel();
                $VendaModel = new VendasModel();
                $result = $VendaReferenciaModel->RetornaVendaReferencia(false);
                if ($result[0]){                    
                    $nroSequencial = $result[1][0]['NRO_SEQUENCIAL'];
                    $result = $VendaReferenciaModel->UpdateVendaReferencia($nroSequencial, 'E');
                    $VendaModel->ReabrirVenda();
                }                
                $retorno[0]=false;
                $retorno[1]=$body->mensagem_sefaz;
            }
        }else{
            if(isset($body->uri)){
                $retorno[0]=true;
                $retorno[1]['nmeCaminhoDanfe'] = $body->uri;        
            }else{
                $retorno[0]=false;
                $retorno[1] = 'Ainda não foi disponibilizado o arquivo PDF da Nota!<br>Tente em alguns instantes!';
            }
        }
        return $retorno;
    }
    
    Public Function ConsultarNotaMercadoria(){
        $server = URL;
        $token= TOKEN;
        $VendaReferenciaModel = new VendaReferenciaModel();
        $result = $VendaReferenciaModel->RetornaVendaReferencia(false, 'P');
        $ref = filter_input(INPUT_POST, 'codVenda', FILTER_SANITIZE_NUMBER_INT)."00".$result[1][0]['NRO_SEQUENCIAL'];   
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $server."/nfe2/consultar.json?ref=" . $ref . "&token=" . $token);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array());
        $body = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch); 
        $body = json_decode($body);
        if ($body!=''){
            if ($body->status=="erro_autorizacao"){
                if (property_exists($body, 'mensagem_sefaz')){
                    $VendaReferenciaModel = new VendaReferenciaModel();
                    $VendaModel = new VendasModel();
                    $result = $VendaReferenciaModel->RetornaVendaReferencia(false);
                    if ($result[0]){                    
                        $nroSequencial = $result[1][0]['NRO_SEQUENCIAL'];
                        $result = $VendaReferenciaModel->UpdateVendaReferencia($nroSequencial, 'E');
                        $VendaModel->ReabrirVenda();
                    }                
                    $retorno[0]=false;
                    $retorno[1]=$body->mensagem_sefaz;
                }
            }else{
                if(isset($body->caminho_danfe)){
                    $retorno[0]=true;
                    $retorno[1]['nmeCaminhoDanfe'] = "https://api.focusnfe.com.br/".$body->caminho_danfe;        
                }else{
                    $retorno[0]=false;
                    $retorno[1] = 'Ainda não foi disponibilizado o arquivo PDF da Nota!<br>Tente em alguns instantes!';
                }
            }
        }else{
            $retorno=null;
        }
        return $retorno;        
    }
    
    Public Function CancelarNota(){
        $VendaReferenciaModel = new VendaReferenciaModel();
        $result = $VendaReferenciaModel->RetornaVendaReferencia(false, 'P');
        $nroSequencial = $result[1][0]['NRO_SEQUENCIAL'];
        if ($nroSequencial>0){
            $result = $this->CancelarNotaMercadoria($nroSequencial);
            if ($result[0]){
                $VendaReferenciaModel->UpdateVendaReferencia($nroSequencial, 'C');
            }
        }
        if ($result[0]){
            $result = $VendaReferenciaModel->RetornaVendaReferencia(false, 'S');
            $nroSequencial = $result[1][0]['NRO_SEQUENCIAL'];
            if ($nroSequencial>0){
                $result = $this->CancelarNotaServico($nroSequencial);
                if ($result[0]){
                    $VendaReferenciaModel->UpdateVendaReferencia($nroSequencial, 'C');
                }
            }
        }
        return json_encode($result);
    }
    
    Public Function CancelarNotaMercadoria($nroSequencial){
        $server = URL;
        $token= TOKEN;
        $ref = filter_input(INPUT_POST, 'codVenda', FILTER_SANITIZE_NUMBER_INT)."00".$nroSequencial;   
        $justificativa = "Teste_de_Cancelamento_de_nota";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $server. "/nfe2/cancelar?token=" . $token . "&ref=" . $ref . "&justificativa=" . $justificativa);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        $body = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($http_code==202){
            $result[0] = true; 
        }else{
            $result[0] = false;
            $result[1] = "A nota fiscal já foi cancelada!";
        }
        curl_close($ch);       
        return $result;
    }

    Public Function CancelarNotaServico($nroSequencial){
        $server = URL;
        $token= TOKEN;
        $ref = filter_input(INPUT_POST, 'codVenda', FILTER_SANITIZE_NUMBER_INT)."00".$nroSequencial;   
        $justificativa = "Teste_de_Cancelamento_de_nota";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $server. "/nfse/" . $ref . ".json?token=" . $token . "&justificativa=".$justificativa);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        $body = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($http_code==202){
            $result[0] = true;
        }else{
            $result[0] = false;
            $result[1] = "A nota fiscal já foi cancelada!";
        }
        curl_close($ch);       
        return $result;
    }
    
    Public Static Function EmitirNotaServico($referencia){
        $server = URL;
        $token= TOKEN;
        $ref = filter_input(INPUT_POST, 'codVenda', FILTER_SANITIZE_NUMBER_INT)."00".$referencia;
        $VendaModel = new VendasModel();
        $dadosVenda = $VendaModel->CarregaDadosVenda(FALSE);
            
        $nfse = array (
                "data_emissao" => $dadosVenda[1][0]['DTA_EMISSAO_NOTA'],
                "incentivador_cultural" => "false",
                "natureza_operacao" => "1",
                "cnpj_emitente" => "26441410000161",
                "optante_simples_nacional" => "true",
                "status" => "1",
                "prestador" => array (
                        "cnpj" => "26441410000161",
                        "inscricao_municipal" => "0767247800177",
                        "codigo_municipio" => "5300108"
                ),
                "servico" => static::RetornaServicosNfe(),
                "tomador" => array (
                        "cpf" => $dadosVenda[1][0]['NRO_CPF'],
                        "razao_social" => $dadosVenda[1][0]['DSC_CLIENTE'],
                        "endereco" => array (
                                "bairro" => $dadosVenda[1][0]['NME_BAIRRO'],
                                "cep" => $dadosVenda[1][0]['NRO_CEP'],
                                "codigo_municipio" => "5300108",
                                "logradouro" => $dadosVenda[1][0]['TXT_LOGRADOURO'],
                                "numero" => $dadosVenda[1][0]['TXT_COMPLEMENTO'],
                                "uf" => $dadosVenda[1][0]['SGL_UF']
                        ),
                ),
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $server . "/nfse" . "?ref=" . $ref . "&token=" . $token);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST,           1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,     json_encode($nfse));
        $body = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch); 
        if ($http_code==400){
            $result[0] = false;
            $result[1] = "A nota já foi gerada e aprovada!";
        }else{
            $result[0] = true;
        }
        $result[2] = $http_code;
        return $result;
    }
    
    Public Static Function RetornaServicosNfe(){
        $NfeDao = new NfeDao();
        $produtosVendas = $NfeDao->RetornaMercadoriasVenda('S');
        $servicos = "";
        $vlrTotal = 0;
        for ($i=0;$i<count($produtosVendas[1]);$i++){
            $servicos .= $produtosVendas[1][$i]['DSC_PRODUTO'];
            $vlrTotal += ($produtosVendas[1][$i]['VLR_VENDA']-$produtosVendas[1][$i]['VLR_DESCONTO'])*$produtosVendas[1][$i]['QTD_VENDIDA'];
            if ($i<count($produtosVendas[1])-1){
                $servicos .= ", ";
            }                                
        } 
        $vlrIss = $vlrTotal*0.06;
        $servicos = array(
            "aliquota" => "0.06",
            "base_calculo" => number_format($vlrTotal,2,'.',''),
            "discriminacao" => trim($servicos),
            "iss_retido" => "0",
            "item_lista_servico" => "14.01",
            "valor_iss" => number_format($vlrIss,2,'.',''),
            "valor_liquido" => number_format($vlrTotal,2,'.',''),
            "valor_servicos" => number_format($vlrTotal,2,'.',''),
            "codigo_cnae" => "4520004"
        );        
//        var_dump($servicos); die;
        
        return $servicos;
    }
}