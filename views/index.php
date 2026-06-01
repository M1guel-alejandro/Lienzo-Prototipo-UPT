<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lienzo</title>
    <link rel="stylesheet" href="/css/styles.css">
  </head>
  <body>
    <div class="topbar">
      <a class="brand" href="/">
        <div class="brand-icon">🖼️</div>
        <div class="brand-title">
          <strong>Lienzo</strong>
          <span>Tu estudio gráfico en línea</span>
        </div>
      </a>
      <div class="topbar-actions" id="topbarActions">
        <a class="button-alt" href="/login">Iniciar sesión</a>
        <a class="button" href="/signup">Crear cuenta</a>
      </div>
    </div>

    <main class="layout">
      <section id="dashboardView" class="view active">
        <div class="hero">
          <div>
            <h1>Bienvenid@ a Lienzo</h1>
            <p>Elige una herramienta del menú para comenzar. Escalado, corrección de color y eliminación de fondo totalmente funcionales.</p>
          </div>
        </div>

        <div class="grid-3">
          <article class="card">
            <div class="module-icon">⬆️</div>
            <span class="tag" style="">Funcional</span>
            <h2>Escalado de imágenes</h2>
            <p>Aumenta la resolución de tus fotos con una interfaz sencilla y arrastrar y soltar archivos.</p>
            <button class="button" data-action="open-module" data-module="upscale">Ir a Escalado</button>
          </article>

          <article class="card">
            <div class="module-icon">🧹</div>
            <span class="tag">Funcional</span>
            <h2>Remover fondo</h2>
            <p>Elimina el fondo de tus imágenes de forma automática y guarda el resultado listo para descargar.</p>
            <button class="button" data-action="open-module" data-module="remove-bg">Ir a Remover Fondo</button>
          </article>

          <article class="card">
            <div class="module-icon">🎨</div>
            <span class="tag">Funcional</span>
            <h2>Corrección de color</h2>
            <p>Ajusta tonos, brillo, contraste y textura con controles estilo Photoshop.</p>
            <button class="button" data-action="open-module" data-module="color-correct">Ir a Corrección de color</button>
          </article>
        </div>
      </section>

      <section id="moduleView" class="view">
        <div class="section-title">
          <div>
            <h2 id="moduleTitle">Módulo</h2>
            <p id="moduleDescription" style="margin: 6px 0 0; color: var(--muted);"></p>
          </div>
          <div class="form-actions">
            <button class="button-alt" id="backToDashboard">Volver al menú</button>
          </div>
        </div>

        <div class="card module-body">
          <div class="module-grid">
            <div class="preview-panel">
              <div class="canvas-area" id="dropZone">
                <h3>Arrastra y suelta tu imagen aquí</h3>
                <p>O haz clic para seleccionar un archivo desde tu equipo.</p>
                <input type="file" id="dropFile" accept="image/*">
              </div>
              <div class="canvas-preview" id="modulePreview"></div>
              <div class="preview-actions" id="moduleActions"></div>
            </div>

            <div class="controls-panel">
              <div class="control-group">
                <label>
                  Enlace de imagen pública (opcional)
                  <input type="text" id="moduleImageUrl" placeholder="https://...">
                </label>
                <div class="control-row" id="upscaleControls">
                  <label>
                    Escala
                    <select id="moduleScale">
                      <option value="2">2x</option>
                      <option value="4">4x</option>
                    </select>
                  </label>
                </div>

                <div class="control-row" id="removeBgControls" style="display:none;">
                  <label>
                    <input type="checkbox" id="removeShadow"> AI Shadow (+3 créditos)
                  </label>
                  <label>
                    <input type="checkbox" id="removeCrop"> Crop to subject
                  </label>
                  <label>
                    Margin
                    <input type="text" id="removeMargin" placeholder="e.g. 10px or 5%">
                  </label>
                </div>

                <div class="control-row" id="moduleActionRow">
                  <button class="button" id="moduleProcess">Procesar imagen</button>
                </div>

                <div id="colorCorrectControls" class="control-group hidden">
                  <div class="control-row">
                    <label>
                      Tono
                      <input type="range" id="hueRange" min="-180" max="180" value="0">
                      <span class="slider-value" id="hueValue">0</span>
                    </label>
                    <label>
                      Saturación
                      <input type="range" id="saturationRange" min="-100" max="100" value="0">
                      <span class="slider-value" id="saturationValue">0</span>
                    </label>
                  </div>
                  <div class="control-row">
                    <label>
                      Brillo
                      <input type="range" id="brightnessRange" min="-100" max="100" value="0">
                      <span class="slider-value" id="brightnessValue">0</span>
                    </label>
                    <label>
                      Contraste
                      <input type="range" id="contrastRange" min="-100" max="100" value="0">
                      <span class="slider-value" id="contrastValue">0</span>
                    </label>
                  </div>
                  <div class="control-row">
                    <label>
                      Exposición
                      <input type="range" id="exposureRange" min="-100" max="100" value="0">
                      <span class="slider-value" id="exposureValue">0</span>
                    </label>
                  </div>
                  <div class="control-group">
                    <label>Curvas</label>
                    <div class="curve-controls">
                      <label>
                        Sombras
                        <input type="range" id="shadowsRange" min="-100" max="100" value="0">
                        <span class="slider-value" id="shadowsValue">0</span>
                      </label>
                      <label>
                        Medios
                        <input type="range" id="midtonesRange" min="-100" max="100" value="0">
                        <span class="slider-value" id="midtonesValue">0</span>
                      </label>
                      <label>
                        Iluminaciones
                        <input type="range" id="highlightsRange" min="-100" max="100" value="0">
                        <span class="slider-value" id="highlightsValue">0</span>
                      </label>
                    </div>
                  </div>

                  <div class="gradient-toggle-row">
                    <span>Mapa de degradado</span>
                    <button type="button" class="button-alt" id="toggleGradientMap">Activar gradiente</button>
                  </div>
                  <div id="gradientEditorWrapper" class="hidden">
                    <div class="gradient-editor">
                      <div class="gradient-editor-header">
                        <div>
                          <h3>Mapa de degradado</h3>
                          <p>Controla los puntos de color y su posición para mapear tonos a colores.</p>
                        </div>
                        <button type="button" class="button-alt" id="addGradientStop">Agregar punto</button>
                      </div>
                      <div class="gradient-stage">
                        <div class="gradient-bar" id="gradientBar"></div>
                        <div class="gradient-stops" id="gradientStops"></div>
                      </div>
                      <div class="gradient-stop-controls" id="gradientStopControls"></div>
                    </div>
                  </div>

                  <button class="button-alt" id="resetCorrections">Restablecer ajustes</button>
                </div>
              </div>

              <div class="note" id="moduleNote"></div>
              <div class="error" id="moduleError"></div>
              <div class="success" id="moduleSuccess"></div>
            </div>
          </div>
        </div>
      </section>

      <footer class="footer">El escalado, la corrección de color y la eliminación de fondo ya están disponibles.</footer>
    </main>

    <script src="/js/script.js"></script>
  </body>
</html>
