<?php


namespace App\Http\Controllers\api;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use DateTime;

class TransitoModel extends Model
{
    static function getDescuentoDetalle($mun, $clave, $fechaInfaccion)
    {
        $descuento = 0;
        $porcentajeBonificacion = 0;
        $porcentajeDescuentoCuota = 0;
        $flagVigencia1 = 0;
        $flagVigencia2 = 0;
        $flagVigencia3 = 0;

        $flagDescuento1 = 0;
        $flagDescuento2 = 0;
        $flagDescuento3 = 0;

        $tipoDesc = 'bonif';

        $queryCuotas = DB::connection('sqlsrv')
            ->table('transimcuotas')
            ->where('clave', '=', trim($clave))
            ->first();
        if ($queryCuotas) {
            if (trim($queryCuotas->bonifica != 'NO')) {
                $fechaInfaccion = date('Y-m-d', strtotime($fechaInfaccion));
                $fechaHoy = date("Y-m-d");

                $queryMunicipio = DB::connection('sqlsrv')
                    ->table('transimmunicipios')
                    ->where('mun', '=', trim($mun))
                    ->first();

                if ($queryMunicipio) {
                    $fechaVigencia1 = date("Y-m-d", strtotime(trim($queryMunicipio->fechavig_1)));
                    if ($fechaHoy <= $fechaVigencia1) {
                        $porcentajeBonificacion = $queryMunicipio->pctbon_1;
                        $flagVigencia1 = 1;
                    }

                    $fechaVigencia2 = date("Y-m-d", strtotime(trim($queryMunicipio->fechavig_2)));
                    if ($fechaHoy <= $fechaVigencia2 && $flagVigencia1 == 0) {
                        $porcentajeBonificacion = $queryMunicipio->pctbon_2;
                        $flagVigencia2 = 1;
                    }

                    $fechaVigencia3 = date("Y-m-d", strtotime(trim($queryMunicipio->fechavig_3)));
                    if ($fechaHoy <= $fechaVigencia3 && $flagVigencia1 == 0 && $flagVigencia2 == 0) {
                        $porcentajeBonificacion = $queryMunicipio->pctbon_3;
                        $flagVigencia3 = 1;
                    }

                    $date1 = new DateTime($fechaInfaccion);
                    $date2 = new DateTime($fechaHoy);
                    $diasDiferencia = abs($date1->diff($date2)->days);
                    if ($diasDiferencia <= $queryCuotas->dias_1) {
                        $porcentajeDescuentoCuota = $queryCuotas->pctdescpp_1;
                        $flagDescuento1 = 1;
                    } else if ($diasDiferencia <= abs($queryCuotas->dias_2) && $flagDescuento1 == 0 && $flagDescuento3 == 0) {
                        $flagDescuento2 = 1;
                        $porcentajeDescuentoCuota = $queryCuotas->pctdescpp_2;
                    } else if($diasDiferencia <= abs($queryCuotas->dias_3) && $flagDescuento1 == 0 && $flagDescuento2 == 0){
                        $flagDescuento3 = 1;
                        $porcentajeDescuentoCuota = $queryCuotas->pctdescpp_3;
                    }


                    if($porcentajeBonificacion > $porcentajeDescuentoCuota)
                    {
                        $descuento = $porcentajeBonificacion;
                        $tipoDesc = 'bonif';
                    } else {
                        $descuento = $porcentajeDescuentoCuota;
                        $tipoDesc = 'descpp';
                    }

                }
            }
        }

        return ["descuento" => $descuento, "tipoDesc" => $tipoDesc];
    }

    static function getPlacas($placa)
    {
        $placas = [];


        // Buscar Placas
        $queryPlaca = DB::connection('sqlsrv')
            ->table('transimgeneral')
            ->where('placa', '=', $placa)
            ->orWhere('placant', '=', $placa)
            ->get();
        foreach ($queryPlaca as $rowPlaca)
        {
            $placa1 = trim($rowPlaca->placa);
            if(!in_array($placa1, $placas))
            {
                $placas[] = $placa1;
            }

            $placa2 = trim($rowPlaca->placant);
            if(strlen($placa2) > 3 && !in_array($placa2, $placas))
            {
                $placas[] = $placa2;
            }

        }

        if(count($placas) == 0)
        {
            $placas[] = $placa;
        }

        return $placas;
    }

    static function getAdeudosPlaca($placa)
    {
        $Total = 0;
        $Descuentos = 0;
        $Neto = 0;
        $Recargos = 0;
        $NetoDescuento281 = 0;
        $NetoDescuento284 = 0;
        $NetoDescuentoGpe = 0;
        $MontoTotalGpe = 0;  //20210623
        $descLinea  = 0; // 20210623
        $descLinea281 = 0;// 20210623
        $descLinea284 = 0;// 20210623


        //1975	FOTO MULTAS TRANSITO --- Boletas que empiecen con 281
        //1976  MULTAS ELECTRONICAS --- Boletas que empiecen con 284
        //1974	MULTAS DE GUADALUPE --- Boletas que NO empiecen con 281 o 284

        $multas281 = 0;
        $multas284 = 0;
        $multasGpe = 0;
        $multas = [];
        $placas = TransitoModel::getPlacas($placa);

        $Hoy = date("Ymd");

        $queryAdeudos = DB::connection('sqlsrv')
            ->table('transidencinf as a')
            ->join('transiddetinf as b', function($join){
                $join->on('a.placa', '=', 'b.placa');
                $join->on('a.boleta', '=', 'b.boleta');
            })
            ->whereIn('a.placa', $placas)
            ->where('a.estatusmov', '=', '0')
            ->where('b.estatusmov', '=', '0')
            ->orderByRaw('a.placa, a.mun, a.fecinf, a.boleta')
            ->select(['a.placa', 'a.boleta', 'a.fecinf', 'a.nomcru', 'a.estatusmov','a.mun', 'a.fechamov', 'a.horamov', 'b.clave', 'b.monto'])
            ->get();

        /* HDT 23/06/2021 Se agrega un 5% de descuento adicional por programa QUE PADRES DESCUENTOS */
        /* GARM 01/10/2021 Se CANCELA descuento adicional por programa QUE PADRES DESCUENTOS */
        $MontoTotalGpe = 0;
        foreach ($queryAdeudos as $rowAdeudo)
        {
            $descuentoBoleta = 0;
            $montoBoleta = round(trim(@$rowAdeudo->monto),2);
            $iniBoleta = substr(trim($rowAdeudo->boleta), 0, 3);

            $munInfo = DB::connection('sqlsrv')->table('transimmunicipios')->where('mun', '=', trim($rowAdeudo->mun))->first();
            $fechaMulta = trim(@$rowAdeudo->fecinf);
            $fechaMulta = date("d/m/Y", strtotime($fechaMulta));
            $infraccionInfo = DB::connection('sqlsrv')->table('transimcuotas')->where('clave', '=', trim(@$rowAdeudo->clave))->first();

            $obj = new \stdClass();
            $obj->mun = trim(@$munInfo->nomcorto);
            $obj->boleta = trim(@$rowAdeudo->boleta);
            $obj->fecinf = $fechaMulta;
            $obj->clave = trim(@$infraccionInfo->descinf);
            $obj->nomcru = trim(@$rowAdeudo->nomcru);
            $obj->monto = number_format(trim(@$montoBoleta),2);

            // Calcular descuentos
            $descuentoA = TransitoModel::getDescuentoDetalle($rowAdeudo->mun, trim(@$rowAdeudo->clave), trim(@$rowAdeudo->fecinf));
            //print_r($descuentoA);
            $descuento = $descuentoA["descuento"];
            $descuentoBoleta = round($montoBoleta * ($descuento / 100), 2);


            //1975	FOTO MULTAS TRANSITO --- Boletas que empiecen con 281
            //1976  MULTAS ELECTRONICAS --- Boletas que empiecen con 284
            //1974	MULTAS DE GUADALUPE --- Boletas que NO empiecen con 281 o 284

            $obj->descuento = $descuentoBoleta;
            $Total += round(trim(@$montoBoleta),2);
            $Descuentos += $descuentoBoleta;

            $multas[] = $obj;

            switch ($iniBoleta)
            {
                case "281":
                    $multas281 += $montoBoleta - $descuentoBoleta;
                    if($descuentoBoleta > 0)
                    {
                        $NetoDescuento281 += ($montoBoleta - $descuentoBoleta);
                        $MontoTotalGpe += $montoBoleta;
                       // $descLinea281 += $montoBoleta * .05;  //072021
                          $descLinea281 += $montoBoleta * .05;  //01112021
                    }
                    break;

                case "284":
                    $multas284 += $montoBoleta - $descuentoBoleta;
                    if($descuentoBoleta > 0)
                    {
                        $NetoDescuento284 += ($montoBoleta - $descuentoBoleta);
                        $MontoTotalGpe += $montoBoleta;
                      //  $descLinea284 += $montoBoleta * .05;  //072021
                        $descLinea284 += $montoBoleta * .05;  //01112021
              
                        
                    }
                    break;

                default:
                    $multasGpe += $montoBoleta - $descuentoBoleta;
                    if($descuentoBoleta > 0)
                    {
                       
                        $NetoDescuentoGpe += ($montoBoleta - $descuentoBoleta);
                        $MontoTotalGpe += $montoBoleta;
                       // $descLinea += $montoBoleta * .05; //072021
                        $descLinea += $montoBoleta * .05; //01112021
                    }
                    break;

            }

        }

        $Neto = $Total - $Descuentos;
        $DescuentosAdicionales = 0;
       
           /* $NetoDescuento281 = round($NetoDescuento281,2);
            $NetoDescuento284 = round($NetoDescuento284,2);
            $NetoDescuentoGpe = round($NetoDescuentoGpe,2);
            $NetoDescuento281 += round($descLinea281 ,2);
            $NetoDescuento284 += round($descLinea284 ,2);
            $NetoDescuentoGpe += round($descLinea,2);*/
            /*$sumNetos = $NetoDescuento281 + $NetoDescuento284 + $NetoDescuentoGpe;*/
            $sumNetos = $descLinea284 + $descLinea281 + $descLinea;
            $DescuentosAdicionales = round($sumNetos,2); 
            $Neto = $Neto - $DescuentosAdicionales;
           
            $multas281 = $multas281 - $descLinea281 ;
            $multas284 = $multas284 - $descLinea284 ;
            $multasGpe = $multasGpe - $descLinea;
       
        
        
        $DescuentosAdicionales =round($descLinea281,2) + round($descLinea284,2) + round($descLinea,2);       

        return [
            "multas" => $multas,
            "Total" => $Total,
            "Descuentos" => $Descuentos,
            "DescuentosAdicionales" => $DescuentosAdicionales,
            "Neto" => $Neto,
            "multas281" => $multas281,
            "multas284" => $multas284,
            "multasGpe" => $multasGpe,
            "netoDescuento281" => $NetoDescuento281,
            "netoDescuento284" => $NetoDescuento284,
            "netoDescuentoGpe" => $NetoDescuentoGpe,
        ];
    }

    static function getTiposPagos()
    {
        return [
            ["descripcion" => "Foto Multas", "variable" => "multas281", "servicio" => "1975"],
            ["descripcion" => "Multas Electr贸nicas", "variable" => "multas284", "servicio" => "1976"],
            ["descripcion" => "Multas de Transito", "variable" => "multasGpe", "servicio" => "1974"],
        ];
    }

    static function getStransmMultas($placa, $servicio)
    {
        return DB::table('transitoLog')->insertGetId(["placa" => $placa, "servicio" => $servicio]);
    }

}