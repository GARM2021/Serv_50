<?php


namespace App\Http\Controllers\api;


use App\Http\Controllers\Controller;
use Codedge\Fpdf\Fpdf\Fpdf;


class PredialController extends Controller
{
    function descargarpdf()
    {

        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        header("Allow: GET, POST, OPTIONS, PUT, DELETE");


        $meses = [
            "01" => "Enero",
            "02" => "Febrero",
            "03" => "Marzo",
            "04" => "Abril",
            "05" => "Mayo",
            "06" => "Junio",
            "07" => "Julio",
            "08" => "Agosto",
            "09" => "Septiembre",
            "10" => "Octubre",
            "11" => "Noviembre",
            "12" => "Diciembre",
        ];

        $fecha = date('d') . ' de ' . $meses[date("m")] . ' del ' . date("Y, H:i");
        $borde = 1;
        $expendiente = trim(@$_POST["expediente"]); //"31168007";
        $correo = trim(@$_POST["correo"]); //"alvaromares@gmail.com";

        \DB::table('predialPdfLog')->insert(["expediente" => $expendiente, "correo" => $correo]);

        if ($expendiente == "") {
            die("FORBIDDEN #REF1");
        }
        $predialInfo = PredialModel::getExpedienteInfo($expendiente);
        if (!$predialInfo) {
            die("FORBIDDEN #REF2");
        }
        if (count($predialInfo->conceptos) == 0) {
            die("FORBIDDEN #REF3");
        }
        $secretarioInfo = PredialModel::getDatosSecretario();

        $pdf = new Fpdf('P', 'cm', 'Letter');
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 12);
        $y = 1;
        $pdf->SetXY(1, $y);
        $pdf->Cell(19.5, 0.5, 'Municipio de Guadalupe, N.L.', 0);
        //dd($_SERVER);
        if (is_file($_SERVER["DOCUMENT_ROOT"] . '/logo-gpe.png')) {
            $pdf->Image($_SERVER["DOCUMENT_ROOT"] . '/logo-gpe.png', 16, 1, 4.2);
        }

        $y += 1;
        $pdf->SetXY(1, $y);
        $pdf->Cell(19.5, 0.5, utf8_decode('Secretaria de Finanzas y Tesorería Municipal'), 0);

        $y += 0.5;
        $pdf->SetXY(1, $y);
        $pdf->Cell(19.5, 0.5, utf8_decode('Dirección de Ingresos y Recaudación Inmobiliaria'), 0);

        $y += 1;
        $pdf->SetFont('Arial', '', 11);
        $pdf->SetXY(1, $y);
        $pdf->Cell(9.75, 0.5, 'Datos del Contribuyente', 0);

        $pdf->SetXY(10.75, $y);
        $pdf->Cell(9.75, 0.5, 'Fecha: ' . $fecha, 0, 0, 'R');

        $y += 0.5;
        $pdf->SetXY(1, $y);
        $pdf->Cell(2.2, 0.7, 'Nombre: ', $borde, 0);
        $pdf->Cell(17.3, 0.7, utf8_decode($predialInfo->nombre), $borde, 0);

        $y += 0.7;
        $pdf->SetXY(1, $y);
        $pdf->Cell(2.2, 0.8, utf8_decode('Domicilio: '), $borde, 0);
        $pdf->Cell(7.55, 0.8, utf8_decode($predialInfo->domicilio), $borde, 0);
        $pdf->Cell(2, 0.8, utf8_decode('Frente: '), $borde, 0);
        $pdf->Cell(2.87, 0.8, utf8_decode($predialInfo->frente), $borde, 0, 'R');
        $pdf->Cell(2, 0.8, utf8_decode('M. Const: '), $borde, 0);
        $pdf->Cell(2.88, 0.8, utf8_decode($predialInfo->mts_construccion), $borde, 0, 'R');

        $y += 0.8;
        $pdf->SetXY(1, $y);
        $pdf->Cell(2.2, 0.8, utf8_decode('Ubicación: '), $borde, 0);
        $pdf->Cell(7.55, 0.8, utf8_decode($predialInfo->ubicacion), $borde, 0);
        $pdf->Cell(2, 0.8, utf8_decode('Fondo: '), $borde, 0);
        $pdf->Cell(2.87, 0.8, utf8_decode($predialInfo->fondo), $borde, 0, 'R');
        $pdf->Cell(2, 0.8, utf8_decode('V. Const: '), $borde, 0);
        $pdf->Cell(2.88, 0.8, utf8_decode($predialInfo->valor_construccion), $borde, 0, 'R');

        $y += 0.8;
        $pdf->SetXY(1, $y);
        $pdf->Cell(2.2, 0.8, utf8_decode('Colonia: '), $borde, 0);
        $pdf->Cell(7.55, 0.8, utf8_decode($predialInfo->colonia), $borde, 0);
        $pdf->Cell(2, 0.8, utf8_decode('Area: '), $borde, 0);
        $pdf->Cell(2.87, 0.8, utf8_decode($predialInfo->area), $borde, 0, 'R');
        $pdf->Cell(2, 0.8, utf8_decode('V. Ter: '), $borde, 0);
        $pdf->Cell(2.88, 0.8, utf8_decode($predialInfo->valor_terreno), $borde, 0, 'R');

        $y += 0.8;
        $pdf->SetXY(1, $y);
        $pdf->Cell(2.2, 0.8, utf8_decode('Expediente: '), $borde, 0);
        $pdf->Cell(7.55, 0.8, utf8_decode($predialInfo->expediente), $borde, 0);
        $pdf->Cell(4.87, 0.8, utf8_decode('Valor Catastral: '), $borde, 0, 'R');
        $pdf->Cell(4.88, 0.8, utf8_decode($predialInfo->valor_catastral), $borde, 0, 'R');

        $y += 0.8;
        $pdf->SetXY(1, $y);
        $pdf->Cell(2.2, 0.8, utf8_decode('Pagado en: '), 0, 0);
        $pdf->Cell(7.55, 0.8, utf8_decode('Pago en Linea'), 0, 0);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(4.87, 0.8, utf8_decode('Recibo: '), 0, 0, 'R');
        $pdf->Cell(4.88, 0.8, utf8_decode($predialInfo->recibo), 0, 0, 'R');

        $pdf->SetFont('Arial', '', 11);
        $y += 0.8;
        $pdf->SetXY(1, $y);
        $pdf->Cell(19.5, 0.8, utf8_decode($predialInfo->concepto), 1);

        $y += 0.8;
        $borde = 0;
        $pdf->SetXY(1, $y);
        $pdf->Cell(6, 0.8, utf8_decode('Concepto'), $borde, 0, 'L');
        $pdf->Cell(2.25, 0.8, utf8_decode('Importe'), $borde, 0, 'R');
        $pdf->Cell(2.25, 0.8, utf8_decode('Recargos'), $borde, 0, 'R');
        $pdf->Cell(2.25, 0.8, utf8_decode('Subtotal'), $borde, 0, 'R');
        $pdf->Cell(2.25, 0.8, utf8_decode('Subsidio'), $borde, 0, 'R');
        $pdf->Cell(2.25, 0.8, utf8_decode('Bonif.'), $borde, 0, 'R');
        $pdf->Cell(2.25, 0.8, utf8_decode('Neto'), $borde, 0, 'R');

        $y += 0.8;
        $pdf->Line(1, $y, 20.5, $y);
        $y += 0.3;
        $borde = 0;
        $pdf->SetFont('Arial', '', 10);
        foreach ($predialInfo->conceptos as $concepto) {
            $pdf->SetXY(1, $y);
            $pdf->MultiCell(6, 0.5, $concepto->concepto, $borde, 'L');
            $pdf->SetXY(7, $y);
            $pdf->Cell(2.25, 0.5, $concepto->importe, $borde, 0, 'R');
            $pdf->Cell(2.25, 0.5, $concepto->recargos, $borde, 0, 'R');
            $pdf->Cell(2.25, 0.5, $concepto->subtotal, $borde, 0, 'R');
            $pdf->Cell(2.25, 0.5, $concepto->subsidio, $borde, 0, 'R');
            $pdf->Cell(2.25, 0.5, $concepto->bonificacion, $borde, 0, 'R');
            $pdf->Cell(2.25, 0.5, $concepto->neto, $borde, 0, 'R');
            $y += (0.5 * $pdf->MultiCellLines);
        }

        $pdf->SetFont('Arial', '', 11);

        $borde = 0;
        $y += 0.2;
        $pdf->Line(1, $y, 20.5, $y);
        $y += 0.2;
        $pdf->SetXY(1, $y);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(6, 0.5, utf8_decode('Total'), $borde, 0, 'R');
        $pdf->Cell(2.25, 0.5, number_format($predialInfo->totalImporte, 2), $borde, 0, 'R');
        $pdf->Cell(2.25, 0.5, number_format($predialInfo->totalRecargos, 2), $borde, 0, 'R');
        $pdf->Cell(2.25, 0.5, number_format($predialInfo->totalSubtotal, 2), $borde, 0, 'R');
        $pdf->Cell(2.25, 0.5, number_format($predialInfo->totalSubsidio, 2), $borde, 0, 'R');
        $pdf->Cell(2.25, 0.5, number_format($predialInfo->totalBonificacion, 2), $borde, 0, 'R');
        $pdf->Cell(2.25, 0.5, number_format($predialInfo->totalNeto, 2), $borde, 0, 'R');

        $pdf->SetFont('Arial', '', 11);
        $y += 1;
        $pdf->SetXY(1, $y);
        $pdf->MultiCell(19.5, 0.5, utf8_decode($predialInfo->totalNetoLetra), $borde, 'L');

        $y = 25;
        if (is_file($_SERVER["DOCUMENT_ROOT"] . '/25f9e794323b453885f5181f1b624d0b.jpg')) {
            $pdf->Image($_SERVER["DOCUMENT_ROOT"] . '/25f9e794323b453885f5181f1b624d0b.jpg', 4, $y-3.5, 5);
        }

        $y = 25;
        $pdf->Line(1, $y, 11, $y);
        $pdf->SetXY(1, $y);
        $pdf->Cell(9.75, 0.6, utf8_decode($secretarioInfo->secretario), 0, 0, 'C');
        $y += 0.5;
        $pdf->SetXY(1, $y);
        $pdf->MultiCell(9.75, 0.8, utf8_decode($secretarioInfo->puesto), 0, 'C');


        if (is_file($_SERVER["DOCUMENT_ROOT"] . '/slogan1.jpg')) {
            $pdf->Image($_SERVER["DOCUMENT_ROOT"] . '/slogan1.jpg', 12, $y-2, 8);
        }


        $fecha = date("YmdHis");
        $archivo ='./tmp/' . md5($expendiente . $fecha) . ".pdf";
        $pdf->Output('F', $archivo);

        $content = '
<p>A quien corresponda:<br><br>
    Se le anexa su recibo de pago de predial almunicipio de Guadalupe N.L.<br><br>
    Para cualquier aclaración favor de comnunicarse al 8030-6000 o acudia a las oficinas de catastro.<br><br>
    Atte:<br>
    Municipio de Guadalupe, N.L.<br>
    Secretaria de Finanzas y Tesorería Municipal<br>
    Dirección de Ingresos y Recaudación Inmobiliaria
</p>';

        $logo = "";
        if (is_file($_SERVER["DOCUMENT_ROOT"] . '/logo-gpe.png')) {
            $logo = asset('logo-gpe.png');
        }

        if ($correo != "") {
            \Mail::send("email_predial", ["content" => $content, "logo" => $logo], function ($message) use ($correo, $archivo) {
                $message->from("predialenlinea@guadalupe.gob.mx", "Predial Guadalupe N.L.");
                $message->to($correo);
                //$message->replyTo($reply_to);
                $message->subject("Comprobante de pago predial");
                $message->attach($archivo);
            });


        }

        echo asset('tmp/' . md5($expendiente . $fecha) . ".pdf");
    }

    function buscar()
    {
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        header("Allow: GET, POST");
        $domicilio = @$_POST["domicilio"];
        $colonia = @$_POST["colonia"];

        $resultados = PredialModel::busquedaPorDireccion($domicilio, $colonia);
        return view('predial_busqueda', ["resultados" => $resultados]);
    }

    public function consulta()
    {
           return view('predial_consulta'); 
        //  return view ('predial_noencontrado'); 
    }

    function cuenta()
    {
        $exp = trim(@$_POST["exp"]);
        if($exp == ""){
            return view('predial_noencontrado');
        }

        $info = PredialModel::getDatosGeneralesExpediente($exp);
        if(!$info){
            return view('predial_noencontrado');
        }

        $marca = trim($info->marca);

        if($marca != "")
        {
            $paga = PredialModel::getExpedienteMarca($marca);
            if(!$paga){
                return view('predial_marca');
            }
        }

        $cuenta = PredialModel::getEstadoCuenta($exp);

        $datos = [];
        $datos["info"] = $info;
        $datos["cuenta"] = $cuenta;
        return view('predial_cuenta', $datos);
    }

    function direccion()
    {
        $domicilio = @$_POST["domicilio"];
        $colonia = @$_POST["colonia"];

        $resultados = PredialModel::busquedaPorDireccion($domicilio, $colonia);
        return view('predial_direccion', ["resultados" => $resultados]);
    }

    function cuenta_pdf()
    {

        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        header("Allow: GET, POST, OPTIONS, PUT, DELETE");


        $meses = [
            "01" => "Enero",
            "02" => "Febrero",
            "03" => "Marzo",
            "04" => "Abril",
            "05" => "Mayo",
            "06" => "Junio",
            "07" => "Julio",
            "08" => "Agosto",
            "09" => "Septiembre",
            "10" => "Octubre",
            "11" => "Noviembre",
            "12" => "Diciembre",
        ];

        $fecha = date('d') . ' de ' . $meses[date("m")] . ' del ' . date("Y, H:i");
        $borde = 1;
        $expendiente = trim(@$_POST["expediente"]); //"31168007";

        if ($expendiente == "") {
            die("ERROR");
        }
        $predialInfo = PredialModel::getDatosGeneralesExpediente($expendiente);
        if (!$predialInfo) {
            die("ERROR");
        }

        $cuenta = PredialModel::getEstadoCuenta($expendiente);

        $pdf = new Fpdf('P', 'cm', 'Letter');
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 12);
        $y = 1;
        $pdf->SetXY(0.5, $y);
        $pdf->Cell(19.5, 0.5, 'Municipio de Guadalupe, N.L.', 0);
        //dd($_SERVER);
        if (is_file($_SERVER["DOCUMENT_ROOT"] . '/logo-gpe.png')) {
            $pdf->Image($_SERVER["DOCUMENT_ROOT"] . '/logo-gpe.png', 16, 1, 4.2);
        }

        $y += 1;
        $pdf->SetXY(0.5, $y);
        $pdf->Cell(19.5, 0.5, utf8_decode('Secretaria de Finanzas y Tesorería Municipal'), 0);

        $y += 0.5;
        $pdf->SetXY(0.5, $y);
        $pdf->Cell(19.5, 0.5, utf8_decode('Dirección de Ingresos y Recaudación Inmobiliaria'), 0);

        $y += 1;
        $pdf->SetFont('Arial', '', 11);
        $pdf->SetXY(0.5, $y);
        $pdf->Cell(9.75, 0.5, 'Datos del Expediente', 0);

        $pdf->SetXY(10.75, $y);
        $pdf->Cell(9.75, 0.5, 'Fecha: ' . $fecha, 0, 0, 'R');


        $y += 0.7;
        $pdf->SetXY(0.5, $y);
        $pdf->Cell(2.2, 0.8, utf8_decode('Domicilio: '), $borde, 0);
        $pdf->Cell(7.55, 0.8, utf8_decode($predialInfo->domubi), $borde, 0);

        $y += 0.8;
        $pdf->SetXY(0.5, $y);
        $pdf->Cell(2.2, 0.8, utf8_decode('Colonia: '), $borde, 0);
        $pdf->Cell(7.55, 0.8, utf8_decode($predialInfo->colubi), $borde, 0);

        $y += 0.8;
        $pdf->SetXY(0.5, $y);
        $pdf->Cell(2.2, 0.8, utf8_decode('Expediente: '), $borde, 0);
        $pdf->Cell(7.55, 0.8, utf8_decode($predialInfo->exp), $borde, 0);

        $y += 1;
        $pdf->SetFont('Arial', '', 11);
        $pdf->SetXY(1, $y);
        $pdf->Cell(9.75, 0.5, 'Estado de Cuenta', 0);



        $y += 0.8;
        $borde = 1;
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetXY(0.5, $y);
        $pdf->Cell(1.5, 0.8, utf8_decode('Año Bim'), $borde, 0, 'C');
        $pdf->Cell(4.5, 0.8, utf8_decode('Concepto'), $borde, 0, 'L');
        $pdf->Cell(2, 0.8, utf8_decode('Fecha'), $borde, 0, 'C');
        $pdf->Cell(2, 0.8, utf8_decode('Importe'), $borde, 0, 'R');
        $pdf->Cell(1.6, 0.8, utf8_decode('Subsidio'), $borde, 0, 'R');
        $pdf->Cell(1.6, 0.8, utf8_decode('Saldo.'), $borde, 0, 'R');
        $pdf->Cell(1.6, 0.8, utf8_decode('Bon. Imp.'), $borde, 0, 'R');
        $pdf->Cell(1.6, 0.8, utf8_decode('Recargos'), $borde, 0, 'R');
        $pdf->Cell(1.6, 0.8, utf8_decode('Bon. Rec.'), $borde, 0, 'R');
        $pdf->Cell(2.2, 0.8, utf8_decode('Neto a Pagar'), $borde, 0, 'R');


        $total1 = 0;
        $total2 = 0;
        $total3 = 0;
        $total4 = 0;
        $total5 = 0;
        $total6 = 0;
        $total7 = 0;
        $total8 = 0;
        $total9 = 0;

        $y += 0.8;
        $pdf->Line(1, $y, 20.5, $y);
        //$y += 0.3;
        $borde = 0;
        $pdf->SetFont('Arial', '', 8.5);
        foreach ($cuenta->adeudos as $rowAdeudo) {

            $total1 += round($rowAdeudo["montoimp"],2);
            $total2 += round($rowAdeudo["salsub"],2);
            $total3 += round($rowAdeudo["saldo"],2);
            $total4 += round($rowAdeudo["bonImp"],2);
            $total5 += round($rowAdeudo["recargos"],2);
            $total6 += round($rowAdeudo["bonRec"],2);
            $total7 += round($rowAdeudo["neto"],2);
            $total8 += round($rowAdeudo["tbonlinea"],2); // 20211202 garm 
            $total9 = $total7 + $total8 ; // 20211202 garm 

            $pdf->SetXY(0.5, $y);
            $pdf->Cell(1.5, 0.5, $rowAdeudo["yearbim"], $borde, 0, 'C');
            $pdf->Cell(4.5, 0.5, utf8_decode($rowAdeudo["descripcion"]), $borde, 0, 'L');
            $pdf->Cell(2, 0.5, $rowAdeudo["fechaven"], $borde, 0, 'C');
            $pdf->Cell(2, 0.5, number_format($rowAdeudo["montoimp"],2), $borde, 0, 'R');
            $pdf->Cell(1.6, 0.5, number_format($rowAdeudo["salsub"],2), $borde, 0, 'R');
            $pdf->Cell(1.6, 0.5, number_format($rowAdeudo["saldo"],2), $borde, 0, 'R');
            $pdf->Cell(1.6, 0.5, number_format($rowAdeudo["bonImp"],2), $borde, 0, 'R');
            $pdf->Cell(1.6, 0.5, number_format($rowAdeudo["recargos"],2), $borde, 0, 'R');
            $pdf->Cell(1.6, 0.5, number_format($rowAdeudo["bonRec"],2), $borde, 0, 'R');
            $pdf->Cell(2.2, 0.5, number_format($rowAdeudo["neto"],2), $borde, 0, 'R');
            $y += 0.5;
        }

        $borde = 0;
        $y += 0.2;
        $pdf->Line(1, $y, 20.5, $y);
        $y += 0.2;
        $pdf->SetXY(1, $y);
        $pdf->SetFont('Arial', 'B', 8.5);
        $pdf->SetXY(0.5, $y);
        $pdf->Cell(8, 0.5, 'Totales', $borde, 0, 'R');
        $pdf->Cell(2, 0.5, number_format($total1,2), $borde, 0, 'R');
        $pdf->Cell(1.6, 0.5, number_format($total2,2), $borde, 0, 'R');
        $pdf->Cell(1.6, 0.5, number_format($total3,2), $borde, 0, 'R');
        $pdf->Cell(1.6, 0.5, number_format($total4,2), $borde, 0, 'R');
        $pdf->Cell(1.6, 0.5, number_format($total5,2), $borde, 0, 'R');
        $pdf->Cell(1.6, 0.5, number_format($total6,2), $borde, 0, 'R');
        $pdf->Cell(2.2, 0.5, number_format($total7,2), $borde, 0, 'R');

        $pdf->SetFont('Arial', '', 11);
        $y += 1;

      
        
        
        $totalLetra = "TOTAL A PAGAR EN LINEA"; 
      if($cuenta->bonEnero > 0){
            $totalLetra = "TOTAL";
            $total = round($total7 - $cuenta->bonEnero,2);
        }

       $pdf->SetXY(1, $y);
        $pdf->Cell(19.7, 0.7, $totalLetra. ": $" . number_format($total7,2), $borde, 0, 'R');
        
      
        
        if($total8 > 0){
          $pdf->SetFont('Arial', '', 11);  
          $y += 1;
          $totalLetra = "TOTAL A PAGAR EN VENTANILLA ";
          $pdf->SetXY(1, $y);
          $pdf->Cell(19.7, 0.7, $totalLetra. ": $" . number_format($total9,2), $borde, 0, 'R');
        }
       
      
        
       
        /*if($cuenta->bonEnero > 0){
            $y+=0.7;
            $pdf->SetXY(1, $y);
            $pdf->Cell(19.7, 0.7, utf8_decode('3% Descuento al IMPUESTO PREDIAL '.date("Y").': $'.number_format($cuenta->bonEnero,2)), $borde, 0, 'R');
            $y+=0.7;
            $pdf->SetXY(1, $y);
            $pdf->Cell(19.7, 0.7, utf8_decode('TOTAL A PAGAR: $'.number_format($total,2)), $borde, 0, 'R');
        }*/

        $y+=1.4;
        $pdf->SetXY(1, $y);
        $pdf->Cell(19.7, 0.7, utf8_decode('NOTA: El importe de la cuenta es válido hasta el día: '. @$cuenta->vence), $borde, 0, 'R');

        $y = 24; //24
       // $y = 25;


        if (is_file($_SERVER["DOCUMENT_ROOT"] . '/slogan1.jpg')) {
            $pdf->Image($_SERVER["DOCUMENT_ROOT"] . '/slogan1.jpg', 12, $y-2, 8);
        }

        $fecha = date("YmdHis");
        $archivo ='./tmp/' . md5($expendiente . $fecha) . ".pdf";
        $pdf->Output('F', $archivo);

        echo asset('tmp/' . md5($expendiente . $fecha) . ".pdf");
    }

}