<?php

require_once('home.php');
require_once('redirect.php');

// Clave principal
$bcdb->current_field = 'codCurso';

$postback = isset($_POST['submit']);
$error = false;

// Si es que el formulario se ha enviado
if($postback) :
  $curso = array(
    'codCurso' => $_POST['codCurso'],
    'nombre' => $_POST['nombre'],
    'creditos' => $_POST['creditos'],
    'activo' => $_POST['activo'],
  );

  // Verificación
  if (empty($curso['codCurso']) || empty($curso['nombre'])) :
    $error = true;
    $msg = "Ingrese la información obligatoria.";
  else :

    $curso = array_map('strip_tags', $curso);
  
    // Guarda el curso
    $id = save_item($_POST['codCurso'], $curso, $bcdb->curso);

    if($id) :
      $msg = "La información se guardó correctamente.";
    else:
      $error = true;
      $msg = "Hubo un error al guardar la información, intente nuevamente.";
    endif;
  endif;
endif;

$pager = true;
$cursos = get_cursos_de_alumno($_SESSION['loginuser']['codAlumno'], get_option('semestre_actual'));

// Paginación.
$results = @$bcrs->get_navigation();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" media="screen" href="/css/reset.css" /> 
<link rel="stylesheet" type="text/css" media="screen" href="/css/text.css" /> 
<link rel="stylesheet" type="text/css" media="screen" href="/css/960.css" /> 
<link rel="stylesheet" type="text/css" media="screen" href="/css/layout.css" /> 
<link href="/favicon.ico" type="image/ico" rel="shortcut icon" />
<script type="text/javascript" src="/scripts/jquery-1.3.2.min.js"></script>
<script type="text/javascript" src="/scripts/jquery.validate.js"></script>
<script type="text/javascript" src="/scripts/jquery.collapsible.js"></script>
<script type="text/javascript" src="/scripts/jquery.jeditable.js"></script>
<title>Cursos | Sistema de exámenes</title>
</head>

<body>
<div class="container_16">
  <div id="header">
    <h1 id="logo"> <a href="/"><span>Sistema de exámenes</span></a> </h1>
    <?php include "menutop.php"; ?>
    <?php if(isset($_SESSION['loginuser'])) : ?>
    <div id="logout">Sesión: <?php print $_SESSION['loginuser']['nombres']; ?> <a href="logout.php">Salir</a></div>
    <?php endif; ?>
  </div>
  <div class="clear"></div>
  <div id="icon" class="grid_3">
    <p class="align-center"><img src="images/opciones.png" alt="Opciones" /></p>
  </div>
  <div id="content" class="grid_13">
    <h1>Cursos</h1>
    <fieldset class="<?php if(!isset($_GET['PageIndex'])): ?>collapsible<?php endif; ?>">
      <legend>Semestre: <?= get_option('semestre_actual') ?></legend>
      <table>
        <thead>
          <tr>
          <th>Código</th>
          <th>Nombre</th>
          <th>Créditos</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($cursos): ?>
          <?php $alt = "even"; ?>
          <?php foreach($cursos as $k => $curso): ?>
          <tr class="<?php print $alt ?>">
            <th><span id="codCurso-<?php print $curso['codCurso']; ?>"><?php print $curso['codCurso']; ?></span></td>
            <th><span id="nombre-<?php print $curso['codCurso']; ?>"><?php print $curso['nombre']; ?></span></td>
            <th><span id="creditos-<?php print $curso['codCurso']; ?>"><?php print $curso['creditos']; ?></td>
            <?php $alt = ($alt == "even") ? "odd" : "even"; ?>
          </tr>
          <?php endforeach; ?>
          <?php else: ?>
          <tr class="<?php print $alt; ?>">
            <td colspan="2">No existen datos</th>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
      <?php include "pager.php"; ?>
    </fieldset>
  </div>
  <div class="clear"></div>
  <?php include "footer.php"; ?>
</div>
</body>
</html>