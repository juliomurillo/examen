<?php

require_once('home.php');
require_once('redirect.php');


$docentes = get_items($bcdb->docente, 'codDocente');
$cursos = get_items($bcdb->curso, 'codCurso');

$postback = isset($_POST['submit']);
$error = false;

// Si es que el formulario se ha enviado
if($postback) :
  $datos = array(
  	'codDocente' => $_POST['codDocente'],
    'codCurso' => $_POST['codCurso'],
	  'codSemestre' => get_option('semestre_actual'),
  );

  // Verificación
  if (empty($datos['codDocente']) || empty($datos['codCurso'])) :
    $error = true;
    $msg = "Ingrese la información obligatoria.";
  else :
    // Guarda la asignacion
    $resultado = save_asignacion($datos);
    
    if($resultado) :
      $msg = "La información se guardó correctamente.";
    else:
      $error = true;
      $msg = "El Curso ya esta Asignado";
    endif;
  endif;
endif;

// Paginación.
$pager = true;
$asignaciones = mostrarAsignaciones();
$results = @$bcrs->get_navigation();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" media="screen" href="<?php print STYLES_URL; ?>reset.css" /> 
<link rel="stylesheet" type="text/css" media="screen" href="<?php print STYLES_URL; ?>text.css" /> 
<link rel="stylesheet" type="text/css" media="screen" href="<?php print STYLES_URL; ?>960.css" /> 
<link rel="stylesheet" type="text/css" media="screen" href="<?php print STYLES_URL; ?>layout.css" /> 
<link href="/favicon.ico" type="image/ico" rel="shortcut icon" />
<script type="text/javascript" src="<?php print SCRIPTS_URL; ?>jquery-1.3.2.min.js"></script>
<script type="text/javascript" src="<?php print SCRIPTS_URL; ?>jquery.collapsible.js"></script>
<script type="text/javascript" src="<?php print SCRIPTS_URL; ?>jquery.jeditable.js"></script>
<script type="text/javascript">
	$(document).ready(function() {
	});
</script>
<title>Asignar Docente a curso | Sistema de exámenes</title>
</head>

<body>
<div class="container_16">
  <?php include "header.php"; ?>
  <div class="clear"></div>
  <div id="icon" class="grid_3">
    <p class="align-center"><img src="<?php print IMAGES_URL; ?>/opciones.png" alt="Opciones" /></p>
  </div>
  <div id="content" class="grid_13">
    <h1>Asignar Docentes a Cursos</h1>
    <?php if (isset($msg)): ?>
    <p class="<?php echo ($error) ? "error" : "msg" ?>"><?php print $msg; ?></p>
    <?php endif; ?>
    <form name="frmasignar" id="frmasignar" method="post" action="asignar-docente.php">
      <fieldset class="collapsible">
      <legend>Asignación</legend>
      <p>
        <label for="codCurso">Curso <span class="required">*</span>:</label>
        <select name="codCurso" id="codCurso">
          <option value="">Seleccione</option>
          <?php foreach($cursos as $k => $curso) : ?>
          <option value="<?php print $curso['codCurso']; ?>"><?php print sprintf("%s: %s", $curso['codCurso'], $curso['nombre']); ?></option>
          <?php endforeach; ?>
        </select>
      </p>
      
      <p>
        <label for="codDocente">Docente <span class="required">*</span>:</label>
        <select name="codDocente" id="codDocente">
          <option value="">Seleccione</option>
          <?php foreach($docentes as $k => $docente) : ?>
          <option value="<?php print $docente['codDocente']; ?>"><?php print $docente['nombres']; ?> <?php print $docente['apellidoP']; ?> <?php print $docente['apellidoM']; ?></option>
          <?php endforeach; ?>
        </select>
      </p>
      <p class="align-center">
        <button type="submit" name="submit" id="submit">Asignar</button>
      </p>
      </fieldset>
    </form>
    <fieldset class="<?php if(!isset($_GET['PageIndex'])): ?>collapsibleClosed<?php else: ?>collapsible<?php endif; ?>">
      <legend>Asignaciones</legend>
      <table>
        <thead>
          <tr>
          <th>Curso</th>
          <th>Docente</th>
          <!--th>Acciones</th-->
          </tr>
        </thead>
        <tbody>
          <?php if ($asignaciones): ?>
          <?php $alt = "even"; ?>
          <?php foreach($asignaciones as $k => $asignacion): ?>
          <tr class="<?php print $alt ?>">
            <td><?php print sprintf('<strong>%s:</strong> %s', $asignacion['codCurso'], $asignacion['nombre']); ?></td>
            <td><?php print $asignacion['nombres']; ?> <?php print $asignacion['apellidoP']; ?> <?php print $asignacion['apellidoM']; ?></td>
            <!--td><a href="asignar-docente.php?op=quitar&amp;codDocente=<?php print $asignacion['codDocente']; ?>&amp;codCurso=<?php print $asignacion['codCurso']; ?>">Quitar</a></td-->
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