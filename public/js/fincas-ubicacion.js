(() => {
    const script = document.querySelector('script[data-estados]');
    const estado = document.querySelector('#estado_clave');
    const municipio = document.querySelector('#municipio_clave');
    const localidad = document.querySelector('#localidad_clave');
    const buscar = document.querySelector('#buscar-localidad');
    const resultados = document.querySelector('#resultados-localidades');
    const seleccionada = document.querySelector('#localidad-seleccionada');
    let indiceActivo = -1;
    const cerrarResultados = () => {
        resultados.hidden = true;
        buscar.setAttribute('aria-expanded', 'false');
        buscar.removeAttribute('aria-activedescendant');
        indiceActivo = -1;
    };
    const confirmarLocalidad = () => {
        const opcion = localidad.selectedOptions[0];
        seleccionada.hidden = !localidad.value;
        seleccionada.textContent = localidad.value ? `✓ Localidad seleccionada: ${opcion.textContent}` : '';
        if (localidad.value) buscar.value = opcion.textContent;
    };
    const mostrarResultados = () => {
        resultados.replaceChildren();
        indiceActivo = -1;
        buscar.removeAttribute('aria-activedescendant');
        for (const opcion of [...localidad.options].filter(opcion => opcion.value)) {
            const boton = document.createElement('button');
            boton.type = 'button';
            boton.tabIndex = -1;
            boton.id = `localidad-opcion-${opcion.value}`;
            boton.setAttribute('role', 'option');
            boton.setAttribute('aria-selected', String(opcion.value === localidad.value));
            boton.textContent = `${opcion.value === localidad.value ? '✓ ' : ''}${opcion.textContent}`;
            boton.addEventListener('pointerdown', event => event.preventDefault());
            boton.addEventListener('click', () => {
                localidad.value = opcion.value;
                confirmarLocalidad();
                cerrarResultados();
                buscar.focus();
            });
            resultados.append(boton);
        }
        resultados.hidden = !resultados.children.length;
        buscar.setAttribute('aria-expanded', String(!resultados.hidden));
    };
    const mensaje = document.querySelector('#ubicacion-estado');
    const mas = document.querySelector('#mas-localidades');
    const reintentar = document.querySelector('#reintentar-ubicacion');
    const form = estado.closest('form');
    let version = 0;
    let pagina = 1;
    let temporizador;
    let pendiente = false;
    let repetir = iniciar;
    const limpiar = (select) => {
        select.replaceChildren(new Option('Seleccione', ''));
        select.disabled = true;
        if (select === localidad) {
            seleccionada.hidden = true;
            cerrarResultados();
        }
    };
    const agregar = (select, datos) => {
        for (const fila of datos) {
            if (![...select.options].some(option => option.value === fila.clave_inegi)) {
                select.add(new Option(fila.nombre, fila.clave_inegi));
            }
        }
        select.disabled = false;
    };
    async function cargar(url) {
        const respuesta = await fetch(url, { headers: { Accept: 'application/json' } });
        if (!respuesta.ok) throw new Error('No se pudo consultar el catálogo.');
        return respuesta.json();
    }
    async function ejecutar(accion) {
        const actual = ++version;
        pendiente = true;
        mensaje.textContent = 'Cargando ubicaciones…';
        reintentar.hidden = true;
        mas.hidden = true;
        repetir = accion;
        try {
            await accion(actual);
        } catch {
            if (actual === version) {
                mensaje.textContent = 'No se pudo cargar la ubicación. Reintenta la consulta.';
                reintentar.hidden = false;
            }
        } finally {
            if (actual === version) pendiente = false;
        }
    }
    async function localidades(actual, anexar = false, inicial = '') {
        const url = new URL(script.dataset.localidades.replace('/00000/', `/${municipio.value}/`));
        url.searchParams.set('buscar', buscar.value);
        url.searchParams.set('page', pagina);
        const datos = await cargar(url);
        if (actual !== version) return;
        const seleccion = inicial || localidad.value;
        if (!anexar) limpiar(localidad);
        agregar(localidad, datos.data);
        if (seleccion && ![...localidad.options].some(option => option.value === seleccion)) {
            const elegido = new URL(url);
            elegido.search = new URLSearchParams({ seleccionada: seleccion });
            const resultado = await cargar(elegido);
            if (actual !== version) return;
            agregar(localidad, resultado.data);
        }
        localidad.value = seleccion;
        confirmarLocalidad();
        if (document.activeElement === buscar) mostrarResultados();
        mas.hidden = !datos.next_page_url;
        buscar.disabled = false;
        mensaje.textContent = datos.data.length ? 'Puedes buscar una localidad o dejarla sin seleccionar.' : 'No hay localidades para esta búsqueda. La localidad es opcional.';
    }
    async function municipios(actual, inicial = '', localidadInicial = '') {
        const datos = await cargar(script.dataset.municipios.replace('/00/', `/${estado.value}/`));
        if (actual !== version) return;
        agregar(municipio, datos.data);
        municipio.value = inicial;
        mensaje.textContent = datos.data.length ? 'Selecciona el municipio.' : 'No hay municipios disponibles para este estado.';
        if (municipio.value) await localidades(actual, false, localidadInicial);
    }
    async function iniciar(actual) {
        limpiar(estado); limpiar(municipio); limpiar(localidad);
        buscar.disabled = true;
        const datos = await cargar(script.dataset.estados);
        if (actual !== version) return;
        agregar(estado, datos.data);
        estado.value = estado.dataset.inicial;
        mensaje.textContent = datos.data.length ? 'Selecciona el estado.' : 'Los catálogos aún no están disponibles. Solicita su importación al administrador.';
        if (!datos.data.length) reintentar.hidden = false;
        if (estado.value) await municipios(actual, municipio.dataset.inicial, localidad.dataset.inicial);
    }
    estado.addEventListener('change', () => {
        clearTimeout(temporizador);
        limpiar(municipio); limpiar(localidad); buscar.value = ''; buscar.disabled = true; pagina = 1;
        ejecutar(async actual => {
            if (estado.value) await municipios(actual);
            else mensaje.textContent = 'Selecciona el estado.';
        });
    });
    municipio.addEventListener('change', () => {
        clearTimeout(temporizador);
        limpiar(localidad); buscar.value = ''; buscar.disabled = true; pagina = 1;
        ejecutar(async actual => {
            if (municipio.value) await localidades(actual);
            else mensaje.textContent = 'Selecciona el municipio.';
        });
    });
    buscar.addEventListener('input', () => {
        clearTimeout(temporizador);
        localidad.value = '';
        seleccionada.hidden = true;
        cerrarResultados();
        ++version;
        pendiente = true;
        mas.hidden = true;
        pagina = 1;
        temporizador = setTimeout(() => ejecutar(actual => localidades(actual)), 350);
    });
    buscar.addEventListener('focus', () => {
        if (!pendiente) mostrarResultados();
    });
    buscar.addEventListener('blur', cerrarResultados);
    buscar.addEventListener('keydown', event => {
        if (event.key === 'Escape') cerrarResultados();
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            if (resultados.hidden && !pendiente) mostrarResultados();
            const opciones = [...resultados.children];
            if (!opciones.length) return;
            indiceActivo = (indiceActivo + (event.key === 'ArrowDown' ? 1 : -1) + opciones.length) % opciones.length;
            opciones.forEach((opcion, indice) => opcion.classList.toggle('activa', indice === indiceActivo));
            buscar.setAttribute('aria-activedescendant', opciones[indiceActivo].id);
            opciones[indiceActivo].scrollIntoView({ block: 'nearest' });
        }
        if (event.key === 'Enter' && !resultados.hidden) {
            event.preventDefault();
            if (indiceActivo >= 0) resultados.children[indiceActivo].click();
        }
    });
    mas.addEventListener('click', () => {
        ++pagina;
        ejecutar(actual => localidades(actual, true));
    });
    reintentar.addEventListener('click', () => ejecutar(repetir));
    form.addEventListener('submit', event => {
        if (buscar.value.trim() && !localidad.value && !pendiente) {
            event.preventDefault();
            event.stopPropagation();
            mensaje.textContent = 'Selecciona una localidad de las sugerencias o borra la búsqueda para dejarla sin seleccionar.';
            buscar.focus();
            return;
        }
        if (pendiente || !estado.value || !municipio.value) {
            event.preventDefault();
            event.stopPropagation();
            mensaje.textContent = 'Selecciona un estado y municipio válidos y espera a que termine la carga.';
        }
    });
    ejecutar(iniciar);
})();
