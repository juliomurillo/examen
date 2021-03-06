<?php
/**
 * Funciones varias
 */

function get_pregunta($codPregunta) {
	global $bcdb;

	$q = sprintf("SELECT * FROM $bcdb->pregunta WHERE codPregunta = '%s'", $codPregunta);
	$pregunta = $bcdb -> get_row($q);

	$q2 = sprintf("SELECT * FROM $bcdb->alternativa WHERE codPregunta = '%s'", $codPregunta);
	$alternativas = $bcdb -> get_results($q2);

	foreach ($alternativas as $alternativa) {
		$pregunta['tema'] = $bcdb -> get_row(sprintf("SELECT * FROM %s WHERE codTema = '%s'", $bcdb -> tema, $pregunta['codTema']));
		$pregunta['curso'] = $bcdb -> get_row(sprintf("SELECT * FROM %s WHERE codCurso = '%s'", $bcdb -> curso, $pregunta['tema']['codCurso']));
		$pregunta['alternativas'][] = $alternativa;
	}
	return $pregunta;
}

/**
 * Devuelve los cursos asignados a un docente
 *
 * @param char $codDocente El código del docente
 * @return array
 */
function get_cursos_docente($codDocente) {
	global $bcdb;

	$q = sprintf("SELECT * 
          FROM %s CA
          INNER JOIN %s DC
          ON CA.codDocente = DC.codDocente
          AND CA.codCurso = DC.codCurso
          INNER JOIN %s C
          ON DC.codCurso = C.codCurso
          WHERE CA.codDocente = '%s'
          AND CA.codSemestre = '%s'", $bcdb -> cargaacademica, $bcdb -> docentecurso, $bcdb -> curso, $codDocente, get_option('semestre_actual'));
	$cursos = $bcdb -> get_results($q);
	return $cursos;
}

/**
 * Trae exámenes de un curso.
 *
 * @param char $codCurso El curso
 * @return array
 */
function get_examenes_curso($codCurso) {
	global $bcdb, $bcrs, $pager;
  
  $examenes = array();

	$sql = sprintf("SELECT DISTINCT e.codExamen, e.nombre, t.codCurso 
    FROM %s e
    INNER JOIN %s ep
    ON e.codExamen = ep.codExamen
    INNER JOIN %s p
    ON ep.codPregunta = p.codPregunta
    INNER JOIN %s t
    ON p.codTema = t.codTema
    WHERE t.codCurso = '%s'
    ORDER BY e.codExamen", $bcdb -> examen, $bcdb -> examenpregunta, $bcdb -> pregunta, $bcdb -> tema, $codCurso);
	$examenes = ($pager) ? $bcrs -> get_results($sql) : $bcdb -> get_results($sql);
  
  if (count($examenes) > 0) {
    foreach ($examenes as $k => $examen) {
      $examenes[$k]['preguntas'] = get_preguntas_de_examen($examen['codExamen']);
    }
  }
	return $examenes;
}

/**
 * Trae temas de un curso
 *
 * @param char $codCurso El curso
 * @param char $codDocente El docente
 * @return array
 */
function get_temas_curso($codCurso, $codDocente) {
	global $bcdb;

	$q = sprintf("SELECT *
    FROM %s T
    WHERE T.codCurso = '%s'
    AND T.codDocente = '%s'", $bcdb -> tema, $codCurso, $codDocente);
	$temas = $bcdb -> get_results($q);
	return $temas;
}

/**
 * Guarda una asignación de un docente
 *
 * @param array $datos Los datos de la asignacion
 * @return boolean
 */
function save_asignacion($datos) {
	global $bcdb;
	$q = sprintf("INSERT INTO %s (codDocente, codCurso)
          VALUES ('%s', '%s')", $bcdb -> docentecurso, $datos['codDocente'], $datos['codCurso']);

	$resultado1 = $bcdb -> query($q);

	$q = sprintf("INSERT INTO %s (codDocente, codCurso, codSemestre)
          VALUES ('%s', '%s', '%s')", $bcdb -> cargaacademica, $datos['codDocente'], $datos['codCurso'], $datos['codSemestre']);

	$resultado2 = $bcdb -> query($q);

	return ($resultado1 && $resultado2);
}

function mostrarAsignaciones() {
	global $bcdb, $bcrs, $pager;
	$sql = sprintf("SELECT D.codDocente, D.nombres, D.apellidoP, D.apellidoM, C.codCurso, C.nombre
        FROM %s CA
        INNER JOIN %s D ON CA.codDocente = D.CodDocente
        INNER JOIN %s C ON CA.codCurso = C.CodCurso", $bcdb -> cargaacademica, $bcdb -> docente, $bcdb -> curso);

	$asignaciones = ($pager) ? $bcrs -> get_results($sql) : $bcdb -> get_results($sql);
	return $asignaciones;
}

/**
 * Guarda un usuario
 *
 * @param int $idusuario El id del usuario
 * @return boolean
 */
function save_user($idusuario, $user_values, $tabla) {
	global $bcdb, $msg;
	if ($idusuario && get_item($idusuario, $tabla)) {
		unset($user_values[$bcdb -> current_field]);
		// We don't want someone 'accidentally' update usuario
	}
	if (($query = insert_update_query($tabla, $user_values)) && $bcdb -> query($query)) {
		if (empty($idusuario))
			$idusuario = $bcdb -> insert_id;
		return $idusuario;
	}
	return false;
}

/**
 * Guarda preguntas relacionadas a un examen.
 * @param array $examen_pregunta los datos de la pregunta y el examen.
 * @return boolean
 */
function save_examen_pregunta($examen_pregunta) {
  global $bcdb;
  
  $sql = sprintf("INSERT INTO %s VALUES ('%s', '%s', '%s')",
          $bcdb->examenpregunta,
          $examen_pregunta['codExamen'],
          $examen_pregunta['codPregunta'],
          $examen_pregunta['puntaje']);
  return ($bcdb->query($sql));
}

/**
 * Devuelve los exámenes creados por un docente.
 * @param string $codDocente el Código del docente.
 * @return array
 */
function get_examenes_docente($codDocente) {
  $results = array();
  $examenes = array();
  $cursos = get_cursos_docente($codDocente);
  foreach ($cursos as $k => $curso) {
    $examen = get_examenes_curso($curso['codCurso']);
    if (count($examen) > 0)
      $results[] = $examen;
  }
  
  foreach ($results as $i => $result) {
    foreach ($result as $j => $v) {
      $examenes[] = $v;
    }
  }
  return $examenes;
}

function get_examenes_programados_docente($codDocente) {
  global $bcdb;
  
  $examenes_existentes = get_examenes_docente($codDocente);
  $examenes = array();
  
  if ($examenes_existentes) :
    foreach ($examenes_existentes as $k => $examen) {
      $sql = sprintf("SELECT EP.*, E.nombre
              FROM %s EP 
              INNER JOIN %s E
              ON EP.codExamen = E.codExamen
              WHERE EP.codExamen = '%s'", 
              $bcdb->examenprograma, 
              $bcdb->examen, 
              $examen['codExamen']);
      $examen = $bcdb->get_row($sql);
      if ($examen) {
        $examen['curso'] = get_curso_de_examen($examen['codExamen']);
        $examenes[] = $examen;
      }
    }
  endif;
  return $examenes;
}

/**
* Es Administrador
*
* @param int $idusuario El id del usuario
* @return boolean
*/
function is_admin($idusuario) {
	return true;
}

/**
 * Devuelve los cursos de un alumno en un semestre.
 * @param type $codAlumno
 * @param type $codSemestre
 * @return type 
 */
function get_cursos_de_alumno($codAlumno, $codSemestre) {
	global $bcdb;
	$sql = "SELECT * FROM tCurso c
INNER JOIN tMatricula m ON c.codCurso = m.codCurso
WHERE (m.codAlumno = '$codAlumno' AND m.codSemestre = '$codSemestre');";

	return $bcdb -> get_results($sql);
}

/**
 * Devuelve examenes pendientes de un alumno.
 * @param type $codAlumno
 * @param type $codCurso
 * @param type $codSemestre
 * @return type 
 */
function get_examenes_pendientes_de_alumno($codAlumno, $codCurso, $codSemestre) {
	global $bcdb;
	$sql = "SELECT DISTINCT
e.codExamen,
e.nombre AS examen,
DATE_FORMAT(ep.fecha, '%m/%d/%Y %h:%i %p') AS fecha,
SEC_TO_TIME(ep.duracion) AS duracion,
TIMEDIFF(ep.fecha, CURRENT_TIMESTAMP()) AS comienzo
FROM tExamenPrograma ep
INNER JOIN tExamen e ON ep.codExamen = e.codExamen
INNER JOIN tExamenPregunta epr ON e.codExamen = epr.codExamen
INNER JOIN tPregunta p ON epr.codPregunta = p.codPregunta
INNER JOIN tTema t ON p.codTema = t.codTema
INNER JOIN tMatricula m ON m.codCurso = t.codCurso
WHERE ep.rendido = 'N' AND m.codCurso = '$codCurso' AND m.codAlumno = '$codAlumno' AND m.codSemestre = '$codSemestre';";

	return $bcdb -> get_results($sql);
}

/**
 *
 * @global type $bcdb
 * @param type $codAlumno
 * @param type $codSemestre
 * @return type 
 */
function get_cursos_con_examenes_pendientes($codAlumno, $codSemestre) {
	global $bcdb;
	$sql = "SELECT DISTINCT c.codCurso, c.nombre
FROM tExamenPrograma ep
INNER JOIN tExamen e ON ep.codExamen = e.codExamen
INNER JOIN tExamenPregunta epr ON e.codExamen = epr.codExamen
INNER JOIN tPregunta p ON epr.codPregunta = p.codPregunta
INNER JOIN tTema t ON p.codTema = t.codTema
INNER JOIN tMatricula m ON m.codCurso = t.codCurso
INNER JOIN tCurso c on t.codCurso = c.codCurso
WHERE ep.rendido = 'N' AND m.codAlumno = '$codAlumno' AND m.codSemestre = '$codSemestre';";

	return $bcdb -> get_results($sql);
}

/**
 *
 * @global type $bcdb
 * @param type $codAlumno
 * @param type $codSemestre
 * @return type 
 */
function get_cursos_con_examenes_rendidos($codAlumno, $codSemestre) {
	global $bcdb;
	$sql = "SELECT DISTINCT c.codCurso, c.nombre
FROM tExamenPrograma ep
INNER JOIN tExamen e ON ep.codExamen = e.codExamen
INNER JOIN tExamenPregunta epr ON e.codExamen = epr.codExamen
INNER JOIN tPregunta p ON epr.codPregunta = p.codPregunta
INNER JOIN tTema t ON p.codTema = t.codTema
INNER JOIN tMatricula m ON m.codCurso = t.codCurso
INNER JOIN tCurso c on t.codCurso = c.codCurso
WHERE ep.rendido = 'S' AND m.codAlumno = '$codAlumno' AND m.codSemestre = '$codSemestre';";

	return $bcdb -> get_results($sql);
}

/**
 * Devuelve examenes rendidos de un alumno.
 * @param type $codAlumno
 * @param type $codCurso
 * @param type $codSemestre
 * @return type 
 */
function get_examenes_rendidos_de_alumno($codAlumno, $codCurso, $codSemestre) {
	global $bcdb;
	$sql = "SELECT DISTINCT
e.nombre AS examen,
DATE_FORMAT(ep.fecha, '%m/%d/%Y %h:%i %p') AS fechaF, e.codExamen, ep.fecha
FROM tExamenPrograma ep
INNER JOIN tExamen e ON ep.codExamen = e.codExamen
INNER JOIN tExamenPregunta epr ON e.codExamen = epr.codExamen
INNER JOIN tPregunta p ON epr.codPregunta = p.codPregunta
INNER JOIN tTema t ON p.codTema = t.codTema
INNER JOIN tMatricula m ON m.codCurso = t.codCurso
WHERE ep.rendido = 'S' AND m.codCurso = '$codCurso' AND m.codAlumno = '$codAlumno' AND m.codSemestre = '$codSemestre';";

	return $bcdb -> get_results($sql);
}

/**
 * Devuelve calificacion de examen de un alumno.
 * @param type $codAlumno
 * @param type $codExamen
 * @param type $fecha
 * @return type 
 */
function get_nota_examen($codAlumno, $codExamen, $fecha) {
	global $bcdb;
	$sql = "SELECT COUNT(*) AS correctas, CASE WHEN ISNULL(SUM(ep.puntaje)) THEN 0 ELSE SUM(ep.puntaje) END AS nota FROM
tRespuesta r
INNER JOIN tAlternativa a ON (a.codAlternativa = r.codAlternativa)
INNER JOIN tPregunta p ON (p.codPregunta = a.codPregunta AND a.correcta = 'S')
INNER JOIN tExamenPregunta ep ON (ep.codExamen = r.codExamen AND ep.codPregunta = p.codPregunta)
WHERE r.codAlumno = '$codAlumno' AND r.codExamen = '$codExamen' AND r.fecha = '$fecha';";

	return $bcdb -> get_results($sql);
}

function get_examen_programa_rendidos($codCurso, $codSemestre) {
	global $bcdb;
	$sql = "SELECT DISTINCT e.codExamen, e.nombre
FROM tExamenPrograma ep
INNER JOIN tExamen e ON ep.codExamen = e.codExamen
INNER JOIN tExamenPregunta epr ON e.codExamen = epr.codExamen
INNER JOIN tPregunta p ON epr.codPregunta = p.codPregunta
INNER JOIN tTema t ON p.codTema = t.codTema
INNER JOIN tMatricula m ON m.codCurso = t.codCurso
INNER JOIN tCurso c on t.codCurso = c.codCurso
WHERE ep.rendido = 'S' AND m.codSemestre = '$codSemestre' AND c.codCurso = '$codCurso';";
	return $bcdb -> get_results($sql);	
}

function get_cursos_con_examen_rendido_por_docente($codDocente, $codSemestre) {
	global $bcdb;
	$sql = "SELECT DISTINCT c.codCurso, c.nombre
FROM tExamenPrograma ep
INNER JOIN tExamen e ON ep.codExamen = e.codExamen
INNER JOIN tExamenPregunta epr ON e.codExamen = epr.codExamen
INNER JOIN tPregunta p ON epr.codPregunta = p.codPregunta
INNER JOIN tTema t ON p.codTema = t.codTema
INNER JOIN tMatricula m ON m.codCurso = t.codCurso
INNER JOIN tCurso c on t.codCurso = c.codCurso
WHERE ep.rendido = 'S' AND m.codDocente = '$codDocente' AND m.codSemestre = '$codSemestre';";
	return $bcdb -> get_results($sql);	
}

function get_fecha_de_examen_programa($codExamen) {
	global $bcdb;
	$sql = "SELECT *, DATE_FORMAT(fecha, '%m/%d/%Y %h:%i %p') AS fechaF FROM tExamenPrograma WHERE codExamen = '$codExamen' AND rendido = 'S';";
	return $bcdb -> get_results($sql);	
}

function get_notas_examen($codCurso, $codSemestre, $codExamen, $fecha, $nota_min) {
	global $bcdb;
	$sql = "SELECT a.codAlumno, a.apellidoP, a.apellidoM, a.nombres, CASE WHEN ISNULL(SUM(ep.puntaje)) THEN 0 ELSE SUM(ep.puntaje) END AS nota
FROM tAlumno a
INNER JOIN tMatricula m ON (m.codAlumno = a.codAlumno)
LEFT JOIN tRespuesta r ON (r.codAlumno = a.codAlumno)
LEFT JOIN tAlternativa alt ON (alt.codAlternativa = r.codAlternativa)
LEFT JOIN tPregunta p ON (p.codPregunta = alt.codPregunta AND alt.correcta = 'S')
LEFT JOIN tExamenPregunta ep ON (ep.codExamen = r.codExamen AND ep.codPregunta = p.codPregunta)
WHERE (m.codCurso = '$codCurso' AND codSemestre = '$codSemestre' AND (r.codExamen = '$codExamen' OR ISNULL(r.codExamen )) AND (r.fecha = '$fecha' OR ISNULL(r.fecha)))
GROUP BY a.codAlumno HAVING nota >= '$nota_min'
ORDER BY nota DESC, a.apellidoP, a.apellidoM, a.nombres;";
	return $bcdb -> get_results($sql);
}

/* FUNCIONES PARA REPORTES */
function get_curso_de_examen($codExamen) {
	global $bcdb;
	$sql = "SELECT DISTINCT c.codCurso, c.nombre FROM tCurso c
INNER JOIN tTema t ON t.codCurso = c.codCurso
INNER JOIN tPregunta p ON p.codTema = t.codTema
INNER JOIN tExamenPregunta ep ON ep.codPregunta = p.codPregunta
WHERE ep.codExamen = '$codExamen';";

	return $bcdb -> get_results($sql);
}

function get_examen($codExamen) {
	global $bcdb;
	$sql = "SELECT * FROM tExamen WHERE codExamen = '$codExamen';";

	return $bcdb -> get_row($sql);
}

function get_preguntas_de_examen($codExamen) {
	global $bcdb;
	$sql = "SELECT * FROM tPregunta p
INNER JOIN tExamenPregunta ep ON p.codPregunta = ep.codPregunta
WHERE ep.codExamen = '$codExamen';";

	return $bcdb -> get_results($sql);
}

function get_alternativas_de_pregunta($codPregunta) {
	global $bcdb;
	$sql = "SELECT * FROM tAlternativa WHERE codPregunta = '$codPregunta'";
	return $bcdb -> get_results($sql);
}

function get_countdown($codExamen, $timestamp) {
  global $bcdb;
  $sql = sprintf("SELECT DISTINCT SUBTIME( SEC_TO_TIME( ep.duracion ) , TIMEDIFF( CURRENT_TIMESTAMP( ) , ep.fecha ) ) 
        AS falta
        FROM tExamenPrograma ep
        WHERE ep.rendido =  'N'
        AND ep.codExamen =  '%s'
        AND ep.fecha = '%s';", $codExamen, $timestamp);

  $falta = $bcdb->get_var($sql);
  
  return ($falta);
}
function get_examen_programado($codExamen, $fecha) {
  global $bcdb;
  $sql = sprintf("SELECT * FROM %s WHERE codExamen = '%s' AND fecha = '%s'",
          $bcdb->examenprograma, $codExamen, $fecha);
  
  return $bcdb->get_row($sql);
}

function get_respuestas_alumno($programado, $codAlumno) {
  global $bcdb;
  $sql = sprintf("SELECT codPregunta, codAlternativa FROM %s
          WHERE codAlumno = '%s'
          AND codExamen = '%s'
          AND fecha = '%s'", 
          $bcdb->respuesta,
          $codAlumno,
          $programado['codExamen'],
          $programado['fecha']);
  
  return $bcdb->get_results($sql);
}

function quitar_imagen($codPregunta) {
  global $bcdb;
  // Seleccionamos la imagen para borrar físicamente.
  $imagen = $bcdb->get_var("SELECT imagen FROM $bcdb->pregunta WHERE codPregunta = '$codPregunta'");
  unlink('archivo/' . $imagen);
  
  // Borramos la imagen de la base de datos.
  $bcdb->query("UPDATE $bcdb->pregunta SET imagen = '' WHERE codPregunta = '$codPregunta'");  
}
?>