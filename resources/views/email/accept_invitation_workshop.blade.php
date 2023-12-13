<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Document</title>
</head>
<body>
  <table border="0" cellpadding="0" cellspacing="0" width="100%" style="table-layout:fixed;background-color:#f9f9f9;height: 100%;" id="bodyTable">
    <tbody>
      <tr>
        <td style="padding:50px 10px;" align="center" valign="top" id="bodyCell">
          
          <table border="0" cellpadding="0" cellspacing="0" width="100%" class="wrapperBody" style="max-width:600px">
            <tbody>
              <tr>
                <td align="center" valign="top">
                  <table border="0" cellpadding="0" cellspacing="0" width="100%" class="tableCard" style="background-color:#fff;border-color:#e5e5e5;border-style:solid;border-width:0 1px 1px 1px;">
                    <tbody>
                      <tr>
                        <td style="background-color:#e31d1a;font-size:1px;line-height:3px" class="topBorder" height="3">&nbsp;</td>
                      </tr>
                      <tr>
                        <td style="padding-top: 60px; padding-bottom: 20px;" align="center" valign="middle" class="emailLogo">
                          
                          <img alt="" border="0" src="http://www.usat.edu.pe/ies/images/servicios/tuempresa/TU_%20EMPRESA.png" style="width:100%;max-width:150px;height:auto;display:block" width="150">
                          
                        </td>
                      </tr>
                      
                      <tr>
                        <td style="padding-bottom: 5px; padding-left: 20px; padding-right: 20px;" align="center" valign="top" class="mainTitle">
                          <h2 class="text" style="color:#000;font-family:Poppins,Helvetica,Arial,sans-serif;font-size:22px;font-weight:500;font-style:normal;letter-spacing:normal;line-height:36px;text-transform:none;text-align:center;padding:0;margin:0">
                            Hola <br>
                            {{$mype->name_complete}}
                          </h2>
                        </td>
                      </tr>
                      <tr>
                        <td style="padding-bottom: 30px; padding-left: 20px; padding-right: 20px;" align="center" valign="top" class="subTitle">
                          <h4 class="text" style="color:#999;font-family:Poppins,Helvetica,Arial,sans-serif;font-size:16px;font-weight:500;font-style:normal;letter-spacing:normal;line-height:24px;text-transform:none;text-align:center;padding:0;margin:0">
                            Taller: {{ $workshop->title }}
                          </h4>
                        </td>
                      </tr>
                      <tr>
                        <td style="padding-left:20px;padding-right:20px" align="center" valign="top" class="containtTable ui-sortable">
                          <table border="0" cellpadding="0" cellspacing="0" width="100%" class="tableDescription" style="">
                            <tbody>
                              <tr>
                                <td style="padding-bottom: 20px;" align="center" valign="top" class="description">
                                  <p class="text" style="color:#666;font-family:'Open Sans',Helvetica,Arial,sans-serif;font-size:14px;font-weight:400;font-style:normal;letter-spacing:normal;line-height:22px;text-transform:none;text-align:center;padding:0;margin:0">
                                    Reciba un cordial saludo por parte del PROGRAMA NACIONAL TU EMPRESA, <br>entidad adscrita al Ministerio de la Producción.
                                  </p>
                                  <br>
                                  <p class="text" style="color:#666;font-family:'Open Sans',Helvetica,Arial,sans-serif;font-size:14px;font-weight:400;font-style:normal;letter-spacing:normal;line-height:22px;text-transform:none;text-align:center;padding:0;margin:0">
                                    🤝🤝🤝 ¡Gracias por registrarte al taller! 🤝🤝🤝 <br>estamos emocionados de contar contigo en este evento que promete <br>ser inspirador y lleno de aprendizaje significativo
                                  </p>
                                  <br>
                                  <p class="text" style="color:#666;font-family:'Open Sans',Helvetica,Arial,sans-serif;font-size:14px;font-weight:400;font-style:normal;letter-spacing:normal;line-height:22px;text-transform:none;text-align:center;padding:0;margin:0">
                                    A continuación, encontrarás la información necesaria <br> para acceder al Taller:
                                  </p>

                                  <p class="text" style="color:#666;font-family:'Open Sans',Helvetica,Arial,sans-serif;font-size:14px;font-weight:400;font-style:normal;letter-spacing:normal;line-height:22px;text-transform:none;text-align:center;padding:0;margin:0">
                                    Fecha: {{ $workshop->workshop_date }}
                                  </p>
                                  <br>
                                  <p class="text" style="color:#666;font-family:'Open Sans',Helvetica,Arial,sans-serif;font-size:14px;font-weight:400;font-style:normal;letter-spacing:normal;line-height:22px;text-transform:none;text-align:center;padding:0;margin:0">
                                    Plataforma: ZOOM
                                  </p>
                      
                                </td>
                              </tr>
                            </tbody>
                          </table>
                          <table>
                            <tbody>
                              <tr>
                                <td style="padding-top:0px;padding-bottom:20px" align="center" valign="top">
                                  <table border="0" cellpadding="0" cellspacing="0" align="center">
                                    <tbody>
                                      <tr>
                                        <td style="background-color:#e31d1a; padding: 12px 35px; border-radius: 50px;" align="center" class="ctaButton"> 
                                          <a href="{{ $workshop->link }}" style="color:#fff;font-family:Poppins,Helvetica,Arial,sans-serif;font-size:13px;font-weight:600;font-style:normal;letter-spacing:1px;line-height:20px;text-transform:uppercase;text-decoration:none;display:block" target="_blank" class="text">
                                            Enlace del taller
                                          </a>
                                        </td>
                                      </tr>
                                    </tbody>
                                  </table>
                                </td>
                              </tr>
                            </tbody>
                          </table>
                          <table>
                            <tr class="padding-botton: 40px;">
                              <p class="text" style="color:#666;font-family:'Open Sans',Helvetica,Arial,sans-serif;font-size:14px;font-weight:400;font-style:normal;letter-spacing:normal;line-height:22px;text-transform:none;text-align:center;padding:0;margin:0">
                                <b>Ingreso libre</b>
                                <br>
                                🎊¡¡¡ Los esperamos !!!🎊
                              </p>
                              <br>
                              <br>
                            </tr>
                          </table>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </td>
              </tr>
            </tbody>
          </table>
  
        </td>
      </tr>
    </tbody>
  </table>
</body>
</html>