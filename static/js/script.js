const views = {
  dashboard: document.getElementById('dashboardView'),
  module: document.getElementById('moduleView'),
};
const backToDashboard = document.getElementById('backToDashboard');
const moduleTitle = document.getElementById('moduleTitle');
const moduleDescription = document.getElementById('moduleDescription');
const moduleNote = document.getElementById('moduleNote');
const moduleError = document.getElementById('moduleError');
const moduleSuccess = document.getElementById('moduleSuccess');
const modulePreview = document.getElementById('modulePreview');
const moduleActions = document.getElementById('moduleActions');
const moduleImageUrl = document.getElementById('moduleImageUrl');
const moduleScale = document.getElementById('moduleScale');
const moduleProcess = document.getElementById('moduleProcess');
const dropZone = document.getElementById('dropZone');
const dropFile = document.getElementById('dropFile');
const colorCorrectControls = document.getElementById('colorCorrectControls');
const hueRange = document.getElementById('hueRange');
const saturationRange = document.getElementById('saturationRange');
const brightnessRange = document.getElementById('brightnessRange');
const contrastRange = document.getElementById('contrastRange');
const exposureRange = document.getElementById('exposureRange');
const shadowsRange = document.getElementById('shadowsRange');
const midtonesRange = document.getElementById('midtonesRange');
const highlightsRange = document.getElementById('highlightsRange');
const gradientBar = document.getElementById('gradientBar');
const gradientStopsLayer = document.getElementById('gradientStops');
const gradientStopControls = document.getElementById('gradientStopControls');
const addGradientStop = document.getElementById('addGradientStop');
const resetCorrections = document.getElementById('resetCorrections');
const toggleGradientMap = document.getElementById('toggleGradientMap');
const gradientEditorWrapper = document.getElementById('gradientEditorWrapper');

let originalImageSrc = null;
let processedImageSrc = null;
let editCanvas = null;
let editContext = null;
let originalImageData = null;
let selectedGradientStopId = 0;
let gradientMapEnabled = false;

const colorSettings = {
  hue: 0,
  saturation: 0,
  brightness: 0,
  contrast: 0,
  exposure: 0,
  shadows: 0,
  midtones: 0,
  highlights: 0,
  gradientStops: [
    { id: 1, position: 0, color: '#2a6cff' },
    { id: 2, position: 1, color: '#ff8b3f' },
  ],
};

const moduleConfig = {
  'upscale': {
    title: 'Escalado de imágenes',
    description: 'Sube una imagen, elige el factor de escala y procesa con la API disponible.',
    status: 'Funcional',
    note: 'Puedes arrastrar una imagen o pegar una URL. Esta función está lista para usar.',
    isAvailable: true,
  },
  'remove-bg': {
    title: 'Remover fondo',
    description: 'Elimina el fondo de tus fotos con un solo clic y descarga el resultado en PNG transparente.',
    status: 'Funcional',
    note: 'Sube una imagen y el sistema eliminará el fondo usando la API configurada en el servidor.',
    isAvailable: true,
  },
  'color-correct': {
    title: 'Corrección de color',
    description: 'Ajusta brillo, contraste, exposición y curvas para lograr un resultado profesional.',
    status: 'Funcional',
    note: 'Sube una imagen y ajusta tono, saturación, brillo, contraste, exposición o filtros de degradado.',
    isAvailable: true,
  },
};

let currentModule = 'upscale';
let currentFile = null;
let currentUser = { logged_in: false };

function showView(name) {
  Object.values(views).forEach((view) => view.classList.remove('active'));
  views[name].classList.add('active');
}

function resetModuleState() {
  moduleError.textContent = '';
  moduleSuccess.textContent = '';
  modulePreview.innerHTML = '';
  moduleActions.innerHTML = '';
  moduleImageUrl.value = '';
  moduleScale.value = '2';
  currentFile = null;
  originalImageSrc = null;
  processedImageSrc = null;
  originalImageData = null;
  editCanvas = null;
  editContext = null;
  resetColorControls();
  setColorControlsVisibility(false);
}

function clamp(value, min, max) {
  return Math.min(max, Math.max(min, value));
}

function lerp(start, end, amount) {
  return start + (end - start) * amount;
}

function updateSliderLabel(range, label) {
  if (label) {
    label.textContent = range.value;
  }
}

function setColorControlsVisibility(isVisible) {
  if (isVisible) {
    colorCorrectControls.classList.remove('hidden');
    document.getElementById('upscaleControls').classList.add('hidden');
    moduleProcess.textContent = 'Aplicar ajustes';
  } else {
    colorCorrectControls.classList.add('hidden');
    document.getElementById('upscaleControls').classList.remove('hidden');
  }
}

function resetColorControls() {
  hueRange.value = '0';
  saturationRange.value = '0';
  brightnessRange.value = '0';
  contrastRange.value = '0';
  exposureRange.value = '0';
  shadowsRange.value = '0';
  midtonesRange.value = '0';
  highlightsRange.value = '0';

  colorSettings.hue = 0;
  colorSettings.saturation = 0;
  colorSettings.brightness = 0;
  colorSettings.contrast = 0;
  colorSettings.exposure = 0;
  colorSettings.shadows = 0;
  colorSettings.midtones = 0;
  colorSettings.highlights = 0;
  colorSettings.gradientStops = [
    { id: 1, position: 0, color: '#2a6cff' },
    { id: 2, position: 1, color: '#ff8b3f' },
  ];
  selectedGradientStopId = colorSettings.gradientStops[0].id;
  gradientMapEnabled = false;
  setGradientEditorVisibility(false);

  updateSliderLabel(hueRange, document.getElementById('hueValue'));
  updateSliderLabel(saturationRange, document.getElementById('saturationValue'));
  updateSliderLabel(brightnessRange, document.getElementById('brightnessValue'));
  updateSliderLabel(contrastRange, document.getElementById('contrastValue'));
  updateSliderLabel(exposureRange, document.getElementById('exposureValue'));
  updateSliderLabel(shadowsRange, document.getElementById('shadowsValue'));
  updateSliderLabel(midtonesRange, document.getElementById('midtonesValue'));
  updateSliderLabel(highlightsRange, document.getElementById('highlightsValue'));
}

function setGradientEditorVisibility(isVisible) {
  if (!gradientEditorWrapper || !toggleGradientMap) {
    return;
  }
  gradientEditorWrapper.classList.toggle('hidden', !isVisible);
  toggleGradientMap.textContent = isVisible ? 'Desactivar gradiente' : 'Activar gradiente';
  gradientMapEnabled = isVisible;
  if (isVisible) {
    renderGradientEditor();
  }
}

function updateColorSetting() {
  colorSettings.hue = Number(hueRange.value);
  colorSettings.saturation = Number(saturationRange.value);
  colorSettings.brightness = Number(brightnessRange.value);
  colorSettings.contrast = Number(contrastRange.value);
  colorSettings.exposure = Number(exposureRange.value);
  colorSettings.shadows = Number(shadowsRange.value);
  colorSettings.midtones = Number(midtonesRange.value);
  colorSettings.highlights = Number(highlightsRange.value);

  updateSliderLabel(hueRange, document.getElementById('hueValue'));
  updateSliderLabel(saturationRange, document.getElementById('saturationValue'));
  updateSliderLabel(brightnessRange, document.getElementById('brightnessValue'));
  updateSliderLabel(contrastRange, document.getElementById('contrastValue'));
  updateSliderLabel(exposureRange, document.getElementById('exposureValue'));
  updateSliderLabel(shadowsRange, document.getElementById('shadowsValue'));
  updateSliderLabel(midtonesRange, document.getElementById('midtonesValue'));
  updateSliderLabel(highlightsRange, document.getElementById('highlightsValue'));

  renderColorPreview();
}

function rgbToHsl(r, g, b) {
  r /= 255;
  g /= 255;
  b /= 255;
  const max = Math.max(r, g, b);
  const min = Math.min(r, g, b);
  let h = 0;
  let s = 0;
  const l = (max + min) / 2;

  if (max !== min) {
    const d = max - min;
    s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
    switch (max) {
      case r:
        h = (g - b) / d + (g < b ? 6 : 0);
        break;
      case g:
        h = (b - r) / d + 2;
        break;
      case b:
        h = (r - g) / d + 4;
        break;
    }
    h /= 6;
  }

  return [h, s, l];
}

function hslToRgb(h, s, l) {
  let r, g, b;

  if (s === 0) {
    r = g = b = l;
  } else {
    const hue2rgb = (p, q, t) => {
      if (t < 0) t += 1;
      if (t > 1) t -= 1;
      if (t < 1 / 6) return p + (q - p) * 6 * t;
      if (t < 1 / 2) return q;
      if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6;
      return p;
    };

    const q = l < 0.5 ? l * (1 + s) : l + s - l * s;
    const p = 2 * l - q;
    r = hue2rgb(p, q, h + 1 / 3);
    g = hue2rgb(p, q, h);
    b = hue2rgb(p, q, h - 1 / 3);
  }

  return [Math.round(r * 255), Math.round(g * 255), Math.round(b * 255)];
}

function hexToRgb(hex) {
  const normalized = hex.replace('#', '');
  const value = normalized.length === 3
    ? normalized.split('').map((ch) => ch + ch).join('')
    : normalized;
  return [
    parseInt(value.slice(0, 2), 16),
    parseInt(value.slice(2, 4), 16),
    parseInt(value.slice(4, 6), 16),
  ];
}

function interpolateColor(colorA, colorB, t) {
  return [
    Math.round(lerp(colorA[0], colorB[0], t)),
    Math.round(lerp(colorA[1], colorB[1], t)),
    Math.round(lerp(colorA[2], colorB[2], t)),
  ];
}

function getGradientColor(luminance) {
  const stops = colorSettings.gradientStops.slice().sort((a, b) => a.position - b.position);
  if (stops.length === 0) {
    return null;
  }
  if (luminance <= stops[0].position) {
    return hexToRgb(stops[0].color);
  }
  if (luminance >= stops[stops.length - 1].position) {
    return hexToRgb(stops[stops.length - 1].color);
  }

  for (let i = 0; i < stops.length - 1; i += 1) {
    const current = stops[i];
    const next = stops[i + 1];
    if (luminance >= current.position && luminance <= next.position) {
      const t = (luminance - current.position) / (next.position - current.position);
      return interpolateColor(hexToRgb(current.color), hexToRgb(next.color), t);
    }
  }

  return hexToRgb(stops[stops.length - 1].color);
}

function renderGradientEditor() {
  if (!gradientBar || !gradientStopControls || !gradientStopsLayer) {
    return;
  }

  const stops = colorSettings.gradientStops.slice().sort((a, b) => a.position - b.position);
  const gradientStopsCss = stops.map((stop) => `${stop.color} ${Math.round(stop.position * 100)}%`).join(', ');
  gradientBar.style.background = `linear-gradient(90deg, ${gradientStopsCss})`;

  gradientStopsLayer.innerHTML = stops
    .map((stop) => `
      <button type="button" class="gradient-stop-point${stop.id === selectedGradientStopId ? ' selected' : ''}"
        style="left: ${Math.round(stop.position * 100)}%; background: ${stop.color};"
        data-stop-id="${stop.id}"></button>
    `)
    .join('');

  gradientStopsLayer.querySelectorAll('.gradient-stop-point').forEach((button) => {
    button.addEventListener('click', (event) => {
      event.stopPropagation();
      selectGradientStop(Number(button.dataset.stopId));
    });
  });

  renderSelectedGradientStopControls();
}

function renderSelectedGradientStopControls() {
  const stop = colorSettings.gradientStops.find((item) => item.id === selectedGradientStopId) || colorSettings.gradientStops[0];
  if (!stop) {
    gradientStopControls.innerHTML = '';
    return;
  }

  const allowDelete = colorSettings.gradientStops.length > 2;
  gradientStopControls.innerHTML = `
    <label>
      Color del punto
      <input type="color" id="gradientStopColor" value="${stop.color}">
    </label>
    <label>
      Posición
      <input type="range" id="gradientStopPosition" min="0" max="100" value="${Math.round(stop.position * 100)}">
      <span class="slider-value" id="gradientStopPositionValue">${Math.round(stop.position * 100)}%</span>
    </label>
    <div class="gradient-stop-actions">
      <button type="button" class="button-alt" id="removeGradientStop" ${allowDelete ? '' : 'disabled'}>Eliminar punto</button>
    </div>
  `;

  document.getElementById('gradientStopColor').addEventListener('input', (event) => {
    stop.color = event.target.value;
    renderGradientEditor();
    renderColorPreview();
  });

  const positionInput = document.getElementById('gradientStopPosition');
  const positionValue = document.getElementById('gradientStopPositionValue');
  positionInput.addEventListener('input', (event) => {
    stop.position = Number(event.target.value) / 100;
    positionValue.textContent = `${event.target.value}%`;
    renderGradientEditor();
    renderColorPreview();
  });

  const removeButton = document.getElementById('removeGradientStop');
  removeButton.addEventListener('click', () => {
    if (!allowDelete) return;
    colorSettings.gradientStops = colorSettings.gradientStops.filter((item) => item.id !== stop.id);
    selectedGradientStopId = colorSettings.gradientStops[0].id;
    renderGradientEditor();
    renderColorPreview();
  });
}

function selectGradientStop(stopId) {
  selectedGradientStopId = stopId;
  renderGradientEditor();
}

function addGradientStopPoint() {
  const stops = colorSettings.gradientStops.slice().sort((a, b) => a.position - b.position);
  const newPosition = Math.min(0.98, Math.max(0.02, (stops[0].position + stops[stops.length - 1].position) / 2));
  const newStop = {
    id: Date.now(),
    position: newPosition,
    color: '#ffffff',
  };
  colorSettings.gradientStops.push(newStop);
  selectedGradientStopId = newStop.id;
  renderGradientEditor();
}

function applyCurves(luminance) {
  const { shadows, midtones, highlights } = colorSettings;
  let result = luminance;
  if (result < 0.5) {
    result += (shadows / 100) * (0.5 - result) * 0.5;
  }
  if (result > 0.5) {
    result += (highlights / 100) * (result - 0.5) * 0.5;
  }
  result += (midtones / 100) * (1 - 2 * Math.abs(result - 0.5)) * 0.2;
  return clamp(result, 0, 1);
}

function applyColorFilters(imageData) {
  const data = imageData.data;
  const { hue, saturation, brightness, contrast, exposure } = colorSettings;
  const satFactor = 1 + saturation / 100;
  const brightOffset = brightness / 100 * 255;
  const exposureOffset = exposure / 100 * 170;
  const contrastFactor = contrast === -100 ? 0 : (259 * (contrast + 255)) / (255 * (259 - contrast));

  for (let i = 0; i < data.length; i += 4) {
    let r = data[i];
    let g = data[i + 1];
    let b = data[i + 2];

    if (hue !== 0 || saturation !== 0) {
      const [h, s, l] = rgbToHsl(r, g, b);
      const newHue = ((h * 360 + hue) % 360 + 360) % 360 / 360;
      const newSat = clamp(s * satFactor, 0, 1);
      [r, g, b] = hslToRgb(newHue, newSat, l);
    }

    r = clamp(contrastFactor * (r - 128) + 128 + brightOffset + exposureOffset, 0, 255);
    g = clamp(contrastFactor * (g - 128) + 128 + brightOffset + exposureOffset, 0, 255);
    b = clamp(contrastFactor * (b - 128) + 128 + brightOffset + exposureOffset, 0, 255);

    const luminosity = (r + g + b) / 765;
    const curveLum = applyCurves(luminosity);
    if (luminosity > 0) {
      const ratio = curveLum / luminosity;
      r = clamp(r * ratio, 0, 255);
      g = clamp(g * ratio, 0, 255);
      b = clamp(b * ratio, 0, 255);
    }

    if (gradientMapEnabled) {
      const mapColor = getGradientColor(luminosity);
      if (mapColor) {
        r = clamp(r * 0.55 + mapColor[0] * 0.45, 0, 255);
        g = clamp(g * 0.55 + mapColor[1] * 0.45, 0, 255);
        b = clamp(b * 0.55 + mapColor[2] * 0.45, 0, 255);
      }
    }

    data[i] = r;
    data[i + 1] = g;
    data[i + 2] = b;
  }
}

function loadImageToCanvas(src) {
  return new Promise((resolve, reject) => {
    const image = new Image();
    image.onload = () => {
      const maxWidth = 900;
      const scale = Math.min(1, maxWidth / image.width);
      editCanvas.width = Math.round(image.width * scale);
      editCanvas.height = Math.round(image.height * scale);
      editContext.clearRect(0, 0, editCanvas.width, editCanvas.height);
      editContext.drawImage(image, 0, 0, editCanvas.width, editCanvas.height);
      originalImageData = editContext.getImageData(0, 0, editCanvas.width, editCanvas.height);
      renderColorPreview();
      resolve();
    };
    image.onerror = reject;
    image.src = src;
  });
}

function showColorCanvas(src) {
  modulePreview.innerHTML = `
    <div class="canvas-preview">
      <canvas id="editCanvas"></canvas>
    </div>
  `;
  editCanvas = document.getElementById('editCanvas');
  editContext = editCanvas.getContext('2d');
  moduleActions.innerHTML = `<button class="button-download" id="moduleDownload">Descargar imagen</button>`;
  document.getElementById('moduleDownload').addEventListener('click', downloadProcessedImage);
  return loadImageToCanvas(src).then(() => {
    if (gradientMapEnabled) {
      renderGradientEditor();
    }
  });
}

function renderColorPreview() {
  if (!originalImageData || !editContext) {
    return;
  }

  const imageData = new ImageData(new Uint8ClampedArray(originalImageData.data), originalImageData.width, originalImageData.height);
  applyColorFilters(imageData);
  editContext.putImageData(imageData, 0, 0);
  processedImageSrc = editCanvas.toDataURL('image/png');
}

function renderModule(moduleKey) {
  const config = moduleConfig[moduleKey];
  currentModule = moduleKey;
  moduleTitle.textContent = config.title;
  moduleDescription.textContent = config.description;
  moduleNote.textContent = config.note;
  moduleProcess.textContent = config.isAvailable ? (moduleKey === 'color-correct' ? 'Aplicar ajustes' : 'Procesar imagen') : 'Módulo en desarrollo';
  moduleProcess.disabled = !config.isAvailable;
  resetModuleState();
const isColorCorrect = moduleKey === 'color-correct';
  const isRemoveBg = moduleKey === 'remove-bg';
  setColorControlsVisibility(isColorCorrect);
  const removeControls = document.getElementById('removeBgControls');
  if (removeControls) removeControls.style.display = isRemoveBg ? 'flex' : 'none';
  const upscaleControls = document.getElementById('upscaleControls');
  if (upscaleControls) upscaleControls.style.display = isRemoveBg ? 'none' : 'flex';
  showView('module');
}

function showPreview(src) {
  modulePreview.innerHTML = `<img src="${src}" alt="Preview de imagen">`;
}

function showBeforeAfter(originalSrc, processedSrc) {
  modulePreview.innerHTML = `
    <div class="compare-grid">
      <div class="compare-panel">
        <h4>Antes</h4>
        <img src="${originalSrc}" alt="Antes">
      </div>
      <div class="compare-panel">
        <h4>Después</h4>
        <img src="${processedSrc}" alt="Después">
      </div>
    </div>
  `;
  moduleActions.innerHTML = `<button class="button-download" id="moduleDownload">Descargar imagen</button>`;
  document.getElementById('moduleDownload').addEventListener('click', downloadProcessedImage);
}

async function downloadProcessedImage() {
  if (!processedImageSrc) {
    moduleError.textContent = 'No hay imagen procesada para descargar.';
    return;
  }

  try {
    let blob;
    if (processedImageSrc.startsWith('data:')) {
      const [header, data] = processedImageSrc.split(',');
      const mimeMatch = header.match(/data:(.*?);/);
      const mime = mimeMatch ? mimeMatch[1] : 'image/png';
      const raw = atob(data);
      const array = new Uint8Array(raw.length);
      for (let i = 0; i < raw.length; i += 1) {
        array[i] = raw.charCodeAt(i);
      }
      blob = new Blob([array], { type: mime });
    } else {
      const response = await fetch(processedImageSrc);
      if (!response.ok) {
        throw new Error('No se pudo descargar la imagen.');
      }
      blob = await response.blob();
    }

    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'lienzo-correccion.png';
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
  } catch (error) {
    moduleError.textContent = 'No se pudo descargar la imagen directamente. Abre la imagen en una nueva pestaña.';
  }
}

function handleFile(file) {
  // prevent anonymous users from using features
  if (!currentUser || !currentUser.logged_in) {
    window.location.href = '/signup';
    return;
  }

  if (!file || !file.type.startsWith('image/')) {
    moduleError.textContent = 'Por favor selecciona un archivo de imagen válido.';
    return;
  }
  moduleError.textContent = '';
  currentFile = file;
  const reader = new FileReader();
  reader.onload = () => {
    originalImageSrc = reader.result;
    if (currentModule === 'color-correct') {
      showColorCanvas(originalImageSrc);
    } else {
      showPreview(originalImageSrc);
    }
  };
  reader.readAsDataURL(file);
}

function validateEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

async function uploadFile(file) {
  const formData = new FormData();
  formData.append('image', file);

  const response = await fetch('/api/images/upload', {
    method: 'POST',
    body: formData,
  });

  if (!response.ok) {
    const data = await response.json().catch(() => ({}));
    const details = data.details ? (typeof data.details === 'string' ? data.details : JSON.stringify(data.details)) : null;
    const message = details ? `${data.error || 'Error al subir la imagen'}: ${details}` : data.error || 'Error al subir la imagen';
    throw new Error(message);
  }

  return response.json();
}

async function sendUpscaleRequest(imageUrl, scale) {
  const response = await fetch('/api/images/upscale', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ image_url: imageUrl, scale }),
  });

  if (!response.ok) {
    const data = await response.json().catch(() => ({}));
    const details = data.details ? (typeof data.details === 'string' ? data.details : JSON.stringify(data.details)) : null;
    const message = details ? `${data.error || 'Error de la API'}: ${details}` : data.error || 'Error de la API';
    throw new Error(message);
  }

  return response.json();
}

async function processModule() {
  moduleError.textContent = '';
  moduleSuccess.textContent = '';

  // require login for processing
  if (!currentUser || !currentUser.logged_in) {
    window.location.href = '/signup';
    return;
  }

  const currentUrl = moduleImageUrl.value.trim();
  const scale = Number(moduleScale.value);

  if (currentModule === 'color-correct') {
    if (!currentFile && !currentUrl) {
      moduleError.textContent = 'Sube un archivo o usa una URL pública de imagen.';
      return;
    }

    moduleSuccess.textContent = 'Aplicando ajustes...';
    updateColorSetting();
    if (originalImageData) {
      renderColorPreview();
      moduleSuccess.textContent = 'Ajustes aplicados correctamente.';
    } else if (currentUrl) {
      try {
        await showColorCanvas(currentUrl);
        renderColorPreview();
        moduleSuccess.textContent = 'Ajustes aplicados correctamente.';
      } catch (error) {
        moduleError.textContent = 'No se pudo cargar la imagen desde la URL. Usa un archivo local o una URL pública válida.';
        moduleSuccess.textContent = '';
      }
    }
    return;
  }

  if (!currentFile && !currentUrl) {
    moduleError.textContent = 'Sube un archivo o usa una URL pública de imagen.';
    return;
  }

  try {
    let imageUrl = currentUrl;
    moduleSuccess.textContent = 'Procesando imagen en el servidor...';

    if (currentModule === 'upscale') {
      if (currentFile) {
        moduleSuccess.textContent = 'Subiendo imagen local...';
        const uploadResponse = await uploadFile(currentFile);
        imageUrl = uploadResponse.image_url;
        showPreview(imageUrl);
      }
      const upscaleResponse = await sendUpscaleRequest(imageUrl, scale);
      if (!upscaleResponse.result_url) throw new Error('La respuesta no contiene result_url.');
      processedImageSrc = upscaleResponse.result_url;
      if (!originalImageSrc) originalImageSrc = imageUrl;
      showBeforeAfter(originalImageSrc, processedImageSrc);
      moduleSuccess.textContent = 'Procesado completo.';
    } else if (currentModule === 'remove-bg') {
      // call remove bg endpoint; allow sending file directly
      let resp;
      if (currentFile) {
        const fd = new FormData();
        fd.append('image', currentFile);
        // append optional remove-bg fields
        const shadowEl = document.getElementById('removeShadow');
        const cropEl = document.getElementById('removeCrop');
        const marginEl = document.getElementById('removeMargin');
        if (shadowEl && shadowEl.checked) fd.append('shadow', JSON.stringify({ enabled: true }));
        if (cropEl && cropEl.checked) fd.append('crop', 'true');
        if (marginEl && marginEl.value) fd.append('margin', marginEl.value);
        const r = await fetch('/api/images/remove_bg', { method: 'POST', body: fd });
        resp = await r.json();
        if (!r.ok) throw new Error(resp.error || 'Error al procesar remove-bg');
      } else {
        // include optional params when using URL
        const shadowEl = document.getElementById('removeShadow');
        const cropEl = document.getElementById('removeCrop');
        const marginEl = document.getElementById('removeMargin');
        const body = { image_url: imageUrl };
        if (shadowEl && shadowEl.checked) body.shadow = { enabled: true };
        if (cropEl && cropEl.checked) body.crop = true;
        if (marginEl && marginEl.value) body.margin = marginEl.value;
        const r = await fetch('/api/images/remove_bg', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
        resp = await r.json();
        if (!r.ok) throw new Error(resp.error || 'Error al procesar remove-bg');
      }
      if (!resp.result_url) throw new Error('La respuesta no contiene result_url.');
      processedImageSrc = resp.result_url;
      if (!originalImageSrc) originalImageSrc = imageUrl || URL.createObjectURL(currentFile);
      showBeforeAfter(originalImageSrc, processedImageSrc);
      moduleSuccess.textContent = 'Fondo eliminado correctamente.';
    }
  } catch (error) {
    moduleError.textContent = error.message;
    moduleSuccess.textContent = '';
  }
}

function setupDragAndDrop() {
  dropZone.addEventListener('click', () => dropFile.click());
  dropZone.addEventListener('dragover', (event) => {
    event.preventDefault();
    dropZone.classList.add('dragover');
  });
  dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
  dropZone.addEventListener('drop', (event) => {
    event.preventDefault();
    dropZone.classList.remove('dragover');
    const file = event.dataTransfer.files[0];
    handleFile(file);
  });
  dropFile.addEventListener('change', () => handleFile(dropFile.files[0]));
}

function init() {
  setupDragAndDrop();
  backToDashboard.addEventListener('click', () => showView('dashboard'));
  moduleProcess.addEventListener('click', processModule);

  fetchCurrentUser();

  [hueRange, saturationRange, brightnessRange, contrastRange, exposureRange, shadowsRange, midtonesRange, highlightsRange].forEach((input) => {
    input.addEventListener('input', updateColorSetting);
  });

  addGradientStop.addEventListener('click', (event) => {
    event.preventDefault();
    addGradientStopPoint();
  });

  toggleGradientMap.addEventListener('click', (event) => {
    event.preventDefault();
    setGradientEditorVisibility(!gradientMapEnabled);
    renderColorPreview();
  });

  resetCorrections.addEventListener('click', (event) => {
    event.preventDefault();
    resetColorControls();
    renderGradientEditor();
    renderColorPreview();
    moduleSuccess.textContent = 'Ajustes restablecidos.';
  });

  document.body.addEventListener('click', (event) => {
    const button = event.target.closest('[data-action="open-module"]');
    if (button) {
      event.preventDefault();
      renderModule(button.dataset.module);
    }
  });
}

init();

async function fetchCurrentUser() {
  try {
    const resp = await fetch('/api/auth/current_user');
    const data = await resp.json();
    currentUser = data;
    // add quick access to history/admin in topbar if present
    const topbarActions = document.getElementById('topbarActions');
    if (topbarActions && currentUser.logged_in) {
      let html = `<span>Hola, ${currentUser.name}</span> <button class="button-alt" id="logoutBtn">Cerrar sesión</button> <a class="button-alt" href="/history">Historial</a>`;
      if (currentUser.role === 'admin') {
        html += ' <a class="button" href="/admin">Admin</a>';
      }
      topbarActions.innerHTML = html;
      document.getElementById('logoutBtn').addEventListener('click', async () => {
        await fetch('/api/auth/logout', { method: 'POST' });
        location.reload();
      });
    }
  } catch (error) {
    // ignore
  }
}
