<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo de pago</title>
    <style>
        body{
            font-family: Arial, Helvetica, sans-serif;
        }
        .container{
            border: 4px double #ccc; 
            padding: 10px;
        }
        .container-header{
            display: flex; 
            flex-direction: row; 
            justify-content: space-between; 
            align-items: center;
        }
        .container-header-logo{
            width: 100px;
            height:100px;
        }
        .container-header-title{
            text-align: center;
            width: 450px; 
            font-size: 20px; 
            font-weight: bold; 
            color:darkblue;
        }
        .container-header-description{
            text-align: center; 
            width: 320px;
        }
        .container-footer{
            font-size:small; 
            text-align: center;
        }
        .container-footer-note{
            font-size:small; 
            text-align: center; 
            border:2px solid; 
            border-radius:5px; 
            padding:3px;
        }
        .text-underline{
            text-decoration:underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <table>
            <tr>
                <td style="text-align: center; width: 105px;">&nbsp;{{-- <img src="{{base_path('archivosPdf/GrupoSion.png')}}" class="container-header-logo" /> --}}</td>
                <td style="text-align: center; width: 250px; font-size: 20px; font-weight: bold; color:darkblue;">PAGOS SION</td>
                <td style="text-align: center; width: 320px;">Hemos efectuado una operacíon Electrónica<br />Por medio del sistema Guate-ACH<br />según detalle</td>
            </tr>
        </table>
        <hr />
        <br />
        <div>
            <span class="text-underline">Información de origen de transacción</span>
            <br />
            <br />
            Banco: {{$dataArchivo['banco_origen']}}
            <br />
            <br />
            Generado por: {{$dataArchivo['generado_por']}}
            <br />
            <br />
            <span class="text-underline">Información de destino de transacción</span>
            <br />
            <br />
            Banco: {{$dataArchivo['banco_destino']}}
            <br />
            <br />
            Cuenta: {{$dataArchivo['cuenta_destino']}}
            <br />
            <br />
            Nombre: {{$dataArchivo['nombre_destino']}}
            <br />
            <br />
            Descripción de pago: {{$dataArchivo['descripcion_pago']}}
            <br />
            <br />
            Crédito de: {{$dataArchivo['monto']}}
            <br />
            <br />
            Fecha de aplicación: {{$dataArchivo['fecha_respuesta']}}
            <br />
            <br />
            <br />
            <br />
            <div class="container-footer">Consulte su estado de cuenta.</div>
            <br />
            <br />
            <div class="container-footer-note">Nota: Este mensaje fue generado automáticamente, favor no responder a este correo.</div>
            <br />
            <br />
        </div>
    </div>
</body>
</html>