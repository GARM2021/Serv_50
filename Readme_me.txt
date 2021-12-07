20211207 >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
PredialMdel.   Se incluyo una variable para que guarde el total bonificado en linea 

  //GRM 07/12/2021 guarda bonificacion en linea para presentar el total neto en el ESTADO DE CUENTA de predial generado en PredialController.php
                        $cantidad = $rowAdeudos->salimp * 0.05;
                        $bonificacionLineaImp =  $cantidad;

    "tbonlinea" => $bonificacionLineaImp // 20211202 garm 



PredialController. Se incluyo las lineas de totales en Ventanilla y totales en Linea . 

 $total8 += round($rowAdeudo["tbonlinea"],2); // 20211202 garm 
            $total9 = $total7 + $total8 ; // 20211202 garm 


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
 <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<       