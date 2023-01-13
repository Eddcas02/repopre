<!DOCTYPE html>
<html>
<head>
    <title>Hi</title>
    <style>
        body{
            font-family: Arial, Helvetica, sans-serif;
        }
#pagos {
  font-family: Arial, Helvetica, sans-serif;
  border-collapse: collapse;
  width: 100%;
}

#pagos td, #pagos th {
  border: 1px solid #ddd;
  padding: 8px;
  font-size: 11px;
}

#pagos tr:nth-child(even){background-color: #f2f2f2;}

#pagos tr:hover {background-color: #ddd;}

#pagos th {
  padding-top: 12px;
  padding-bottom: 12px;
  text-align: left;
  background-color: #B2B2B2;
}
</style>
</head>
<body>
    <br />
    <table style="width:100%; border-collapse: collapse;">
        <tr>
            <td style="text-align: center; width:15%;border: 1px solid #ddd;"><img src="{{base_path('archivosPdf/GrupoSion.png')}}" style="width: 80px; height:80px" /></td>
            <td style="text-align: center; width: 55%;font-size: 20px;border: 1px solid #ddd;"><strong>LISTADO DE PAGOS<br />AUTORIZADOS</strong></td>
            <td style="width:30%;font-size: 12px;border: 1px solid #ddd;">
                <table>
                    <tr><td><strong>Código:</strong></td><td>FZ-RG-0130</td></tr>
                    <tr><td><strong>Fecha Aprobación:</strong></td><td>Julio 2020</td></tr>
                    <tr><td><strong>Sub-Proceso:</strong></td><td>TESORERÍA</td></tr>
                    <tr><td><strong>Versión:</strong></td><td>01</td></tr>
                </table>
            </td>
        </tr>
    </table>
    <br />
    <br />
    <br />
    <table style="width:100%; border-collapse: collapse;">
        <tr>
            <td style="text-align: center; width: 50%;"><strong>Fecha autorización:</strong> {{$dataArchivo['fecha']}}</td>
            <td style="text-align: center; width: 50%;"><strong>Hora autorización:</strong> {{$dataArchivo['hora']}}</td>
        </tr>
    </table>
    <br />
    <br />
    <br />
    <table id="pagos">
        <tr>
            <th style="text-align: center">No. Cuenta</th>
            <th style="text-align: center">Cheque</th>
            <th style="text-align: center">Fecha</th>
            <th style="text-align: center">Beneficiario</th>
            <th style="text-align: center">Moneda</th>
            <th style="text-align: center">Valor</th>
        </tr>
    @foreach ($dataArchivo['flujos'] as $key=>$flujo)
    <tr>
        <td style="width:15%;">{{ $flujo['CuentaOrigen'] }}</td>
        <td style="width:15%;">{{ $flujo['Cheque'] }}</td>
        <td style="width:12%;">{{ $flujo['aut_date'] }}</td>
        <td style="width:39%;">{{ $flujo['EnFavorDe'] }}</td>
        <td style="width:9%;">{{ $flujo['DocCurr'] }}</td>
        <td style="width:10%;">{{ $flujo['DocTotal'] }}</td>
    </tr>
    @endforeach
    </table>
    <br />
    <br />
    <div style="text-align: right; width:100%">
        <img src="data:image/png;base64, {!! $dataArchivo['CodigoQR'] !!}" />
            </div>
</body>
</html>