<?php


namespace App\Http\Controllers\api;

use App\Http\Controllers\api\DigitoVerificador\DVOxxo;
use App\Http\Controllers\api\DigitoVerificador\DVPaynet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Luecano\NumeroALetras\NumeroALetras;
use function GuzzleHttp\Psr7\str;


class PredialModel extends Model
{
    static function getExpedienteInfo($expediente)
    {

        $datosInfo = PredialModel::getDatosGeneralesExpediente($expediente);
        if (!$datosInfo) {
            return null;
        }
        $res = new \stdClass();
        $res->expediente = $expediente;
        $res->nombre = trim(@$datosInfo->apat) . ' ' . trim($datosInfo->amat) . ' ' . trim($datosInfo->nombre);
        $res->domicilio = trim(@$datosInfo->dompart);
        $res->frente = trim(@$datosInfo->frente) != "" ? number_format(trim(@$datosInfo->frente), 1) : "";
        $res->ubicacion = trim(@$datosInfo->domubi);
        $res->mts_construccion = trim(@$datosInfo->areaconst);
        $res->fondo = trim(@$datosInfo->fondo) != "" ? number_format(trim(@$datosInfo->fondo), 2) : "";
        $res->valor_construccion = trim(@$datosInfo->valconst) != "" ? number_format(trim(@$datosInfo->valconst), 2) : "";
        $res->colonia = trim(@$datosInfo->colubi);
        $res->area = trim(@$datosInfo->areater) != "" ? number_format(trim(@$datosInfo->areater), 2) : "";
        $res->valor_terreno = trim(@$datosInfo->valter) != "" ? number_format(trim(@$datosInfo->valter), 2) : "";
        $res->valor_catastral = trim(@$datosInfo->valcat) != "" ? number_format(trim(@$datosInfo->valcat), 2) : "";

        $res->concepto = 'IMPUESTO PREDIAL';
        $detalle = PredialModel::getPagosPredial($expediente);
        $conceptos = $detalle->conceptos;

        $res->conceptos = $conceptos;

        $res->totalImporte = $detalle->totalImporte;
        $res->totalRecargos = $detalle->totalRecargos;
        $res->totalSubtotal = $detalle->totalSubtotal;
        $res->totalSubsidio = $detalle->totalSubsidio;
        $res->totalBonificacion = $detalle->totalBonificacion;
        $res->totalNeto = $detalle->totalNeto;

        $NaL = NumeroALetras::convert($res->totalNeto, 'PESOS', true);

        $res->totalNetoLetra = '(* * *) ' . $NaL;

        $res->recibo = $detalle->recibo;


        return $res;
    }

    static function getDatosSecretario()
    {
        $obj = new \stdClass();
        $obj->secretario = 'LIC. JOSE ALEJANDRO ESPINOZA EGUIA';
        $obj->puesto = 'Secretario de Finanzas y Tesorero Municipal';
        return $obj;
    }

    static function getDatosGeneralesExpediente($expediente)
    {
        $query = DB::connection('sqlsrv')
            ->table('preddexped')
            ->where('exp', '=', trim($expediente))
            ->first();

        return $query;
    }

    static function getExpedienteMarca($marca)
    {
        $queryMarca = DB::connection('sqlsrv')
            ->table('predmmarcas')
            ->where('marca', '=', $marca)
            ->first();
        if (!$queryMarca) {
            return true;
        }

        if (trim($queryMarca->paga) == 'NO' || trim($queryMarca->paga) == '') {
            return false;
        }

        return true;
    }

    static function getPagosPredial($expediente)
    {
        $sql = "SELECT SUBSTRING(a.yearbim, 1, 4) + '-' + SUBSTRING(a.yearbim, 5, 6) AS yearbim, SUBSTRING(a.recibo, 1, 4) + '-' + SUBSTRING(a.recibo, 5, 8) AS recibo, 
                SUBSTRING(a.fpago, 7, 2) + '/' + SUBSTRING(a.fpago, 5, 2) + '/' + SUBSTRING(a.fpago, 1, 4) AS fpago, 
                a.montoimp, a.subsidio, a.bonif, a.recargos, a.bonrec, b.descripcion AS descripcion
                FROM preddpagos a INNER JOIN  predmtpocar b ON a.tpocar COLLATE DATABASE_DEFAULT = b.tpocar COLLATE DATABASE_DEFAULT
                WHERE (a.exp='$expediente') AND (a.estatus = '0000')
                ORDER BY a.recibo DESC, a.yearbim";
        $query = \DB::connection('sqlsrv')
            ->select(\DB::raw($sql));
        $conceptos = [];
        $recibo = "";
        $totalImporte = 0;
        $totalRecargos = 0;
        $totalSubtotal = 0;
        $totalSubsidio = 0;
        $totalBonificacion = 0;
        $totalNeto = 0;
        foreach ($query as $row) {
            if ($recibo == "" && $row->recibo != "") {
                $recibo = $row->recibo;
            }

            if ($row->recibo == $recibo) {
                $obj = new \stdClass();
                $obj->concepto = trim($row->descripcion) . ' Bim. ' . trim($row->yearbim);
                $obj->yearBim = trim($row->yearbim);
                $obj->descripcion = trim($row->descripcion);
                $obj->importe = number_format($row->montoimp, 2);
                $obj->recargos = number_format($row->recargos, 2);
                $obj->subtotal = number_format($row->montoimp + $row->recargos, 2);
                $obj->subsidio = number_format($row->subsidio, 2);
                $obj->bonificacion = number_format($row->bonif + $row->bonrec, 2);
                $obj->neto = number_format(($row->montoimp + $row->recargos) - $row->subsidio - ($row->bonif + $row->bonrec), 2);
                $obj->fechaPago = trim($row->fpago);
                $obj->recibo = $recibo;

                $obj->bonif = number_format($row->bonif, 2);
                $obj->bonrec = number_format($row->bonrec, 2);

                $conceptos[] = $obj;


                $totalImporte += $row->montoimp;
                $totalRecargos += $row->recargos;
                $totalSubtotal += $row->montoimp + $row->recargos;
                $totalSubsidio += $row->subsidio;
                $totalBonificacion += $row->bonif + $row->bonrec;
                $totalNeto += $row->montoimp + $row->recargos - $row->subsidio - ($row->bonif + $row->bonrec);
            }
        }
        $result = new \stdClass();
        $result->conceptos = $conceptos;
        $result->totalImporte = $totalImporte;
        $result->totalRecargos = $totalRecargos;
        $result->totalSubtotal = $totalSubtotal;
        $result->totalSubsidio = $totalSubsidio;
        $result->totalBonificacion = $totalBonificacion;
        $result->totalNeto = $totalNeto;
        $result->recibo = $recibo;
        //dd($result);
        return $result;
    }

    static function busquedaPorDireccion($direccion, $colonia)
    {
        $where = "";
        $aDir = explode(" ", $direccion);
        $whereDir = [];
        foreach ($aDir as $dir) {
            $whereDir[] = " domubi LIKE '%$dir%' ";
        }

        $aCol = explode(" ", $colonia);
        $whereCol = [];
        foreach ($aCol as $col) {
            $whereCol[] = " colubi LIKE '%$col%' ";
        }

        //$where .= " AND ( ".implode(" OR ", $whereDir)." ) ";
        //$where .= " AND ( ".implode(" OR ", $whereCol)." ) ";

        $where .= " AND ( domubi LIKE '%$direccion%' ) ";
        $where .= " AND ( colubi LIKE '%$colonia%' ) ";


        $sql = "SELECT TOP 15 exp, domubi, colubi FROM preddexped WHERE exp != '' $where ORDER BY domubi, colubi, exp";
        //echo $sql;
        return DB::connection('sqlsrv')->select(DB::raw($sql));
    }

    static function getFechaLetra($fecha)
    {
        $dia = array("Domingo", "Lunes", "Martes", "Miercoles", "Jueves", "Viernes", "S谩bado");
        $mes = array('mes', "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");

        $nDia = date("w", strtotime($fecha));
        $sDia = date("d", strtotime($fecha));
        $sMes = date("n", strtotime($fecha));
        $sYear = date("Y", strtotime($fecha));

        return $dia[$nDia] . ' ' . $sDia . ' de ' . $mes[$sMes] . ' del ' . $sYear;
    }

    static function getEstadoCuenta($expediente)
    {
        $connection = 'sqlsrv';
        $response = new \stdClass();

        
        /* de prueba se agrega un dia mas 
        $hdthoy = trim(date("Ymd"));
        $hoy = date("Ymd",strtotime($hdthoy."+ 1 days")); */
        
        $hoy = date("Ymd");
        
        
        $mun = 28; // Municipio de Guadalupe, N.L.
        $totalAdeudo = 0;
        $totalImpuesto = 0;
        $bonEnero = 0;
        $Adeudos = [];
        $yearBimOrder = "";
        $response->comorder_id = "";
        $descBonLinea = (int)env('DESCUENTO');

        $queryExp = DB::connection($connection)
            ->table('preddexped')
            ->where('exp', '=', $expediente)
            ->where('fbaja', '<', '00000001')
            ->first();

        if (!$queryExp) {
            return null;
        }

        $marca = trim($queryExp->marca);
        $paga = PredialModel::getExpedienteMarca($marca);
        if (!$paga) {
            return null;
        }

        $queryAdeudos = DB::connection($connection)
            ->table('preddadeudos')
            ->where('exp', '=', $expediente)
            ->where('estatus', '<', '0001')
            ->where('salimp', '>', 0)
            ->orderBy('yearbim')
            ->get();

        if (count($queryAdeudos) > 0) {
            $fechaUltimoMes = date("Y-m-t");
            $response->vence = PredialModel::getFechaLetra($fechaUltimoMes);
            $response->venceFecha = $fechaUltimoMes;
            $descuentoAdicionalFlag = true;
            foreach ($queryAdeudos as $rowAdeudos) {

                $bimSem = trim($rowAdeudos->bimsem);
                $yearBim = trim($rowAdeudos->yearbim);
                if (substr($yearBim, 0, 4) == date("Y")) {
                    $yearBimOrder += $yearBim;
                }
                $diaMes = date('j');
                $recargos = 0;
                $fechaVencimiento = trim($rowAdeudos->fechaven);
                $bonificacionImp = 0;
                $bonificacionLineaImp = 0; //20211202 GARM para acumular la bonificacion en linea y presentarla en el estado de cuenta impreso
                $bonificacionRec = 0;
                $tipoCargo = trim($rowAdeudos->tpocar);

                $queryTipoCargo = DB::connection($connection)
                    ->table('predmtpocar')
                    ->where('tpocar', '=', $tipoCargo)
                    ->first();

                /************************************
                 *  PASO 1: Calcular Bonificaci贸nes del importe
                 */


                $queryBonificacion = DB::connection($connection)
                    ->table('bondbonpred')
                    ->where('tpocar', '=', $tipoCargo)
                    ->where('fecini', '<=', $hoy)
                    ->where('fecfin', '>=', $hoy)
                    ->where('estatus', '=', '0')
                    ->first();
                    
                /* HDT 06/01/2021 Se busca en la BDD si es comercio */
                /*$esComercio = DB::connection($connection)
                    ->table("predmtpoconst")
                    ->leftJoin("preddtpoconst", "predmtpoconst.tpoconst", "=", "preddtpoconst.tpoconst")
                    ->where("preddtpoconst.exp", "=", trim($expediente))
                    ->where("predmtpoconst.habita", "=", "0")
                    ->first();*/
                    
                /* HDT 07/01/2021 Se busca en la BDD si es lote baldio */
                /*$esBaldio = DB::connection($connection)
                    ->table("Preddtpoconst")
                    ->where("Preddtpoconst.exp", "=", trim($expediente))
                    ->first();*/

                if ($queryBonificacion) {
                    /* && !$esComercio && $esBaldio*/
                    if($tipoCargo == '002'){
                        
                        //GRM 07/12/2021 guarda bonificacion en linea para presentar el total neto en el ESTADO DE CUENTA de predial generado en PredialController.php
                        $cantidad = $rowAdeudos->salimp * 0.05;
                        $bonificacionLineaImp =  $cantidad;
                        
                        /* HDT 01/01/2021 se agrega el + 3 que un 3% adicional por pago en linea siempre y cuando el tipo de construccion sea vivienda && el no sea baldío */
                        /* HDT 22/06/2021 se agrega el + 5 que es un 5% adicional por pago en linea del programa QUE PADRES DESCUENTOS  */
                        /* GRM 26/10/2021 se OMITIO  el + 5 que es un 5% adicional por pago en linea del programa QUE PADRES DESCUENTOS  */
                         /* GRM 01/11/2021 se ACTIVA  el + 5 que es un 5% adicional por pago en linea del programa   */
                        $bonImpPaso1 = (($rowAdeudos->salimp - $rowAdeudos->salsub) * ($queryBonificacion->pctbonimp + 5)) / 100;    
                        /*$bonImpPaso1 = (($rowAdeudos->salimp - $rowAdeudos->salsub) * ($queryBonificacion->pctbonimp)) / 100;    */
                    } else {
                        $bonImpPaso1 = (($rowAdeudos->salimp - $rowAdeudos->salsub) * $queryBonificacion->pctbonimp) / 100;
                    }
                    
                    $bonImpPaso2 = $bonImpPaso1 * 10;
                    $bonImpPaso3 = (int)$bonImpPaso2;
                    $bonificacionImp = $bonImpPaso3 / 10;
                }

                /*
                 * 13 Mayo 2020 se toman las bonificaciones primero de la tabla preddadeudos, si esta en 0 se toma lo de la tabla de bondbonpred
                 */

                if($rowAdeudos->impbon > 0)
                {
                    /* && !$esComercio && $esBaldio*/
                    if($tipoCargo == '002'){
                        /* HDT 01/01/2021 se busca en salimp la cantidad de descuento, y se obtiene el 3% siempre y cuando el tipo de construccion sea vivienda && el no sea baldío*/
                       /* GARM 09/11/2021 solo actua el impbon > 0 que esta definido en el preddadeudos y se aplican % 10 + 5 vigentes mas  5 % pago en linea mas (NO 5%  por BUEN FIN 21)  total 20% */
                      
                        $cantidad = $rowAdeudos->salimp * 0.05;
                        /* HDT 01/01/2021 el 3% obtenido (adicional de descuento por pago en linea) se suma a la cantidad del importe a pagar de descuento */
                        $bonificacionImp = $rowAdeudos->impbon + $cantidad;
                        $bonificacionLineaImp =  $cantidad; // 20211202 garm acumula la bonif en linea para presentarla en la impresion del Edo de Cta.
                        /* HDT 01/01/2021 se realiza redondeo del importe .5 baja a .0, .6 sube al siguiente entero */
                        $bonificacionImp = round($bonificacionImp, 1, PHP_ROUND_HALF_DOWN);
                    } else {
                        $bonificacionImp = $rowAdeudos->impbon;
                        /* HDT 01/01/2021 se realiza redondeo del importe .5 baja a .0, .6 sube al siguiente entero */
                        $bonificacionImp = round($bonificacionImp, 1, PHP_ROUND_HALF_DOWN);
                    }
                    
                    $descuentoAdicionalFlag = false;
                }

                /*******************************************
                 * TERMINA PASO 1: Calcular Bonificaciones del importe
                 */


                /*******************************************
                 * PASO 2: Calcular Recargos
                 */

                $wbSYB = $bimSem . $yearBim;
                if ($bimSem == '06') {
                    $wbSYB = $bimSem . substr($yearBim, 0, 4) . '04'; // Cambia a 04
                }

                // Verificar si el expediente se encuentra requerido
                $queryRequerido = DB::connection($connection)
                    ->table('preddrequer')
                    ->where('exp', '=', $expediente)
                    ->orderBy('freq', 'desc')
                    ->first();

                $tablaRecargos = 'predmtabrec';

                if ($queryRequerido) {
                    $fechaRequerimiento = $queryRequerido->freq;
                    if ($fechaRequerimiento >= $fechaVencimiento) {
                        $tablaRecargos = 'predmtabrec2';
                    }
                }


                $queryRecargos = DB::connection($connection)
                    ->table($tablaRecargos)
                    ->where('bsyb', '=', $wbSYB)
                    ->first();

                if (!$queryRecargos) {
                    $queryRecargos = DB::connection($connection)
                        ->table($tablaRecargos)
                        ->orderBy('bsyb')
                        ->first();
                }

                if ($queryRecargos) {
                    $campoRecargos = 'pctrec_' . date('n');
                    if ($diaMes == 1) {
                        $mesAnterior = date('n') - 1;
                        if ($mesAnterior <= 0) {
                            $mesAnterior = 1;
                        }

                        $campoRecargos = 'pctrec_' . $mesAnterior;
                    }

                    $recPaso1 = ($rowAdeudos->salimp * $queryRecargos->$campoRecargos) / 100;
                    $recPaso2 = $recPaso1 * 10;
                    $recPaso3 = (int)$recPaso2;
                    $recargos = $recPaso3 / 10;
                } else {
                    $campoRecargos = 'pctrec_' . date('n');
                    $queryRecargos2 = DB::connection($connection)
                        ->table('predmtabrec')
                        ->whereRaw("(SUBSTRING(bsyb, 1, 2) ='" . $bimSem . "')")
                        ->first();
                    if ($queryRecargos2) {
                        $recPaso1 = ($rowAdeudos->salimp * $queryRecargos2->$campoRecargos) / 100;
                        $recPaso2 = $recPaso1 * 10;
                        $recPaso3 = (int)$recPaso2;
                        $recargos = $recPaso3 / 10;
                    }
                }

                /*******************************************
                 * TERMINA PASO 2: Calcular Recargos
                 */


                if ($tipoCargo > '0002') {
                    $recargos = 0;
                }

                /*******************************************
                 * PASO 3: Calcular Bonificaciones de los recargos
                 */

                $queryBonificacionRecargos = DB::connection($connection)
                    ->table('bondbonpred')
                    ->where('tpocar', '=', $tipoCargo)
                    ->where('fecini', '<=', $hoy)
                    ->where('fecfin', '>=', $hoy)
                    ->where('estatus', '=', '0')
                    ->first();

                if ($queryBonificacionRecargos) {
                    $bonRecPaso1 = ($recargos * $queryBonificacionRecargos->pctbonrec) / 100;
                    $bonRecPaso2 = $bonRecPaso1 * 10;
                    $bonRecPaso3 = (int)$bonRecPaso2;
                    $bonificacionRec = $bonRecPaso3 / 10;
                }

                /*******************************************
                 * TERMINA PASO 3: Calcular Bonificaciones de los recargos
                 */

                $neto = (round($rowAdeudos->salimp, 2) + round($recargos, 2)) - (round($bonificacionImp, 2) + round($bonificacionRec, 2) + round($rowAdeudos->salsub, 2));
                $totalAdeudo = round($totalAdeudo, 2) + round($neto, 2);

                /*******************************************
                 * PASO 4: Calcular 3% extra de bonificaci贸n en enero, en pago en linea
                 */

                if ($rowAdeudos->salimp > 0 && substr($yearBim, 0, 4) == date("Y")) {
                    $totalImpuesto += $neto;
                }

                /*******************************************
                 * TERMINA PASO 4: Calcular 3% extra de bonificaci贸n en enero, en pago en linea
                 */

                if ($rowAdeudos->salimp > 0) {
                    $Adeudos[] = [
                        "yearbim" => $yearBim,
                        "descripcion" => @$queryTipoCargo->descripcion,
                        "fechaven" => date("d/m/Y", strtotime($rowAdeudos->fechaven)),
                        "montoimp" => round($rowAdeudos->montoimp, 2),
                        "salsub" => round($rowAdeudos->salsub, 2),
                        "saldo" => round($rowAdeudos->salimp, 2) - round($rowAdeudos->salsub, 2),
                        "bonImp" => round($bonificacionImp, 2),
                        "recargos" => round($recargos, 2),
                        "bonRec" => round($bonificacionRec, 2),
                        "neto" => $neto,
                        "tbonlinea" => $bonificacionLineaImp // 20211202 garm 
                    ];
                }

            }

            if (date("n") <= 5 && $descuentoAdicionalFlag) {
                $queryBonEnero = DB::connection($connection)
                    ->table("predexpdesc")
                    ->where('exp', '=', $expediente)
                    ->first();

                if ($queryBonEnero || env('DESCUENTO_TODOS') == "SI") {
                    $bonEnero = round($totalImpuesto * ($descBonLinea / 100), 2);
                }
            }

            $response->bonEnero = $bonEnero;
            $response->comorder_id = trim(substr(trim($expediente) . $yearBimOrder, 0, 32));
        }

        $response->adeudos = $Adeudos;
        $response->totalAdeudo = $totalAdeudo;

        $response->pagos = PredialModel::getPagosPredial($expediente);

        return $response;

    }

    static function getReferenciaPaynet($expediente, $fecha, $total)
    {
        $issuer = "00006D";
        $fechaFormato = date("dmy", strtotime($fecha));
        $monto = number_format($total, 2, '.', '');
        $aMonto = explode('.', $monto);
        if (count($aMonto) > 1) {
            $monto = $aMonto[0] . str_pad($aMonto[1], 2, "0", STR_PAD_RIGHT);
        } else {
            $monto = $aMonto[0] . "00";
        }

        $monto = str_pad($monto, 8, "0", STR_PAD_LEFT);

        $ref1 = $issuer . $expediente . $monto . $fechaFormato;
        $dv = new DVPaynet();
        $cv1 = $dv->generate($ref1);
        return $ref1 . $cv1;
    }

    static function getReferenciaOxxo($expediente, $fecha, $total)
    {
        $issuer = "9901";
        if (strlen($expediente) == 8 && substr($expediente, 0, 2) != "28") {
            $expediente = "28" . $expediente;
        }

        if (strlen($expediente) <> 10) {
            return false;
        }

        $fechaFormato = date("Ymd", strtotime($fecha));
        $monto = number_format($total, 2, '.', '');
        $aMonto = explode('.', $monto);
        if (count($aMonto) > 1) {
            $monto = $aMonto[0] . str_pad($aMonto[1], 2, "0", STR_PAD_RIGHT);
        } else {
            $monto = $aMonto[0] . "00";
        }

        $monto = str_pad($monto, 7, "0", STR_PAD_LEFT);

        $ref1 = $issuer . $expediente . $fechaFormato . $monto;
        $dv = new DVOxxo();
        $cv1 = $dv->generar_dv($ref1);
        return $ref1 . $cv1;
    }

    static function getReferenciaBancoAzteca($expediente, $fecha, $total)
    {
        $issuer = "9901";
        if (strlen($expediente) == 8 && substr($expediente, 0, 2) != "28") {
            $expediente = "28" . $expediente;
        }

        if (strlen($expediente) <> 10) {
            return false;
        }

        $fechaFormato = date("dmY", strtotime($fecha));
        $monto = number_format($total, 2, '.', '');
        $aMonto = explode('.', $monto);
        if (count($aMonto) > 1) {
            $monto = $aMonto[0] . str_pad($aMonto[1], 2, "0", STR_PAD_RIGHT);
        } else {
            $monto = $aMonto[0] . "00";
        }

        $monto = str_pad($monto, 7, "0", STR_PAD_LEFT);

        $ref1 = $issuer . $expediente . $fechaFormato . $monto;
        $dv = new DVOxxo();
        $cv1 = $dv->generar_dv($ref1);
        return $ref1 . $cv1;
    }
}
