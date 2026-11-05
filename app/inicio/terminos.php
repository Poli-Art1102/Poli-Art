<?php
declare(strict_types=1);

$project_base = '/poliart/';
require_once __DIR__ . '/header.php';
?>

<style>
  .terms-page { width: min(960px, calc(100% - 2rem)); margin: 2rem auto; padding: 2.25rem; color: var(--text-main); background: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: 1.25rem; box-sizing: border-box; }
  .terms-page h1 { margin: 0 0 .5rem; color: var(--brand-orange); font-size: clamp(1.6rem, 3vw, 2.3rem); }
  .terms-back { display: inline-flex; align-items: center; gap: .4rem; margin-bottom: 1.25rem; padding: .55rem .9rem; border: 1px solid var(--border-subtle); border-radius: .65rem; color: var(--text-main); text-decoration: none; font-weight: 700; background: var(--hover-item); }
  .terms-back:hover { border-color: var(--brand-orange); color: var(--brand-orange); }
  .terms-page h2 { margin: 1.8rem 0 .55rem; color: var(--brand-orange); font-size: 1.15rem; }
  .terms-page p, .terms-page li { color: var(--text-muted); line-height: 1.65; }
  .terms-page ul { padding-left: 1.3rem; }
  .terms-page .terms-date { margin-top: 0; font-size: .9rem; }
  .terms-page a { color: var(--brand-orange); font-weight: 700; }
  @media (max-width: 640px) { .terms-page { padding: 1.25rem; } }
</style>

<section class="terms-page" aria-labelledby="terms-title">
  <a class="terms-back" href="<?= $project_base ?>app/inicio/index.php" onclick="if (document.referrer) { event.preventDefault(); history.back(); }">← Volver</a>
  <h1 id="terms-title">📜 Términos y Condiciones</h1>
  <p class="terms-date">Última actualización: 25 de octubre de 2026</p>
  <p>Estos términos regulan el uso de Poli-Art, una plataforma para descubrir, publicar y adquirir obras de arte. Al crear una cuenta o utilizar el sitio, aceptas estas condiciones y nuestra Política de Privacidad.</p>

  <h2>1. Registro y cuenta</h2>
  <p>Debes proporcionar información verdadera, completa y actualizada. Cada persona es responsable de proteger sus credenciales y de toda actividad realizada desde su cuenta. No está permitido crear cuentas para suplantar a otra persona o evadir una restricción.</p>
  <h2>2. Publicación de obras</h2>
  <p>Quien publica una obra declara que tiene los derechos o autorizaciones necesarios para compartirla y, cuando corresponda, ofrecerla. El contenido no debe infringir derechos de autor, marcas, privacidad, imagen ni ninguna norma aplicable.</p>
  <h2>3. Contenido prohibido</h2>
  <p>No se permite publicar contenido ilegal, fraudulento, engañoso, discriminatorio, que incite a la violencia, que vulnere a menores o que infrinja derechos de terceros. Poli-Art puede ocultar o retirar publicaciones que incumplan estas reglas y revisar los reportes recibidos.</p>
  <h2>4. Compras, reservas y colaboraciones</h2>
  <p>Los usuarios deben entregar información honesta sobre precios, disponibilidad y condiciones de cada obra o colaboración. Las partes deben comunicarse de forma respetuosa y cumplir los acuerdos que acepten. Poli-Art podrá intervenir ante reportes, pero no sustituye los acuerdos ni las obligaciones legales entre usuarios.</p>
  <h2>5. Uso aceptable</h2>
  <p>Está prohibido intentar acceder sin autorización a cuentas o sistemas, alterar el funcionamiento del sitio, distribuir código malicioso, usar automatizaciones abusivas o recopilar datos de otros usuarios sin autorización.</p>
  <h2>6. Propiedad intelectual</h2>
  <p>El sitio, su marca, diseño y funcionalidades pertenecen a sus respectivos titulares. Cada creador conserva sus derechos sobre sus obras. Al publicar, otorgas a Poli-Art una licencia limitada, no exclusiva y gratuita para alojar, reproducir técnicamente y mostrar el contenido dentro del servicio.</p>
  <h2>7. Disponibilidad y cambios</h2>
  <p>Podemos actualizar, suspender o retirar funcionalidades para mantener y mejorar el servicio. También podemos modificar estos términos; publicaremos la versión vigente en esta página e indicaremos su fecha de actualización.</p>
  <h2>8. Contacto</h2>
  <p>Para preguntas, solicitudes o reportes relacionados con estos términos, utiliza los canales de contacto disponibles en Poli-Art.</p>
  <p>Si no estás de acuerdo con estos términos, no debes crear una cuenta ni continuar utilizando las funciones que requieren aceptación.</p>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
