<!doctype html>
<html lang="en">
  <head>
    <title>Send Email in Laravel 8 Using Gmail SMTP | Programming Fields</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
  </head>
  <body>

    <div class="container">
        <div class="row">
            <div class="col-xl-6 col-lg-6 col-sm-12 m-auto">
                <p> Buen día, </p>
                <p> Tiene un nuevo pago pendiente por autorizar. Número de pago: <a href="https://pagos.sion.com.gt/pagos/#/redireccion/pago/{{ $details['id_flujo'] }}">{{ $details['doc_num'] }}</a></p>
                <br/>
                <br/>
                <p>Saludos,</p>
            </div>
        </div>
    </div>
  </body>
</html>