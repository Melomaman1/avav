# 🛡️ Sistema Anti-Bots Mejorado - Instrucciones

## ✅ Cambios Implementados

### 1. **Backend - Detección de Patrones de Bots** (`z7k2m_secure_handler.php`)
- ✅ **Threshold reducido**: De 5 a 3 intentos por IP antes de bloqueo permanente
- ✅ **Lista expandida de palabras comunes**: Agregadas 40+ palabras usadas por bots
- ✅ **10 patrones de detección** que bloquean automáticamente:
  - Patrón A: `usuario.punto.números` + `clave simple` (ej: betsabe.fernandez + bonito97)
  - Patrón B: `usuario.punto.números` + `clave con símbolos` (ej: jorge_quesada.26 + azul$@3%)
  - Patrón C: Claves formato bot (ej: GC726%08, TC56317&)
  - Patrón D: Claves aleatorias largas (ej: yoxuwaludu26, bonoqihasupi95)
  - Patrón E: Claves simples (ej: luna57, hola33, casa66)
  - Patrón F: Nombres propios + números (ej: Christopher49, Esther31)
  - Patrón G: Usuario terminado en números + clave común

### 2. **Frontend - Validación Anti-Bot** (`pcindex.html`)
- ✅ **Detección de automatización**: WebDriver, Puppeteer, Selenium, Headless Chrome
- ✅ **Interacción humana requerida**:
  - PC: Mínimo 5 movimientos de mouse + 5 teclas presionadas
  - Móvil: Mínimo 3 teclas + 1 clic
- ✅ **Tiempo mínimo**: 2 segundos desde carga de página
- ✅ **Fingerprinting avanzado**: Detecta propiedades de automatización

### 3. **Script de Análisis** (`analyze_and_block_bots.php`)
- ✅ Analiza logs automáticamente
- ✅ Bloquea IPs con 3+ intentos sospechosos
- ✅ Detecta patrones de bot en el log

---

## 🚀 Cómo Usar

### **Opción 1: Ejecutar Manualmente**
Abre PowerShell en la carpeta del proyecto y ejecuta:
```powershell
php analyze_and_block_bots.php
```

### **Opción 2: Automatizar con Task Scheduler (Recomendado)**

1. Abre **Task Scheduler** (Programador de tareas)
2. Crea una nueva tarea:
   - **Nombre**: Bloquear Bots Avanz
   - **Trigger**: Cada 1 hora
   - **Action**: 
     - Programa: `php.exe`
     - Argumentos: `C:\Users\USER\Documents\W\av definitivo\analyze_and_block_bots.php`
     - Iniciar en: `C:\Users\USER\Documents\W\av definitivo`

---

## 📊 Monitoreo

### Ver IPs bloqueadas:
```powershell
Get-Content "C:\Users\USER\Documents\W\av definitivo\blocked_ips.txt"
```

### Ver log de bloqueos:
```powershell
Get-Content "C:\Users\USER\Documents\W\av definitivo\blocked_log.txt" | Select-Object -Last 50
```

### Contar IPs bloqueadas:
```powershell
(Get-Content "C:\Users\USER\Documents\W\av definitivo\blocked_ips.txt").Count
```

---

## 🔧 Ajustes Adicionales (Opcional)

### Si los bots siguen pasando:

1. **Reducir más el threshold** en `z7k2m_secure_handler.php`:
   ```php
   $threshold = 2; // De 3 a 2 intentos
   ```

2. **Aumentar interacción requerida** en `pcindex.html`:
   ```javascript
   if (_mouseMovements < 10 || _keyPresses < 10) return true;
   ```

3. **Activar reCAPTCHA** (desactivado actualmente):
   - Descomentar código reCAPTCHA en `z7k2m_secure_handler.php` (líneas 264-290)
   - Agregar script reCAPTCHA en HTML

---

## ⚠️ Notas Importantes

1. **Archivo `proces.php`**: Es un SEÑUELO/TRAMPA. Los errores de lint son intencionales. Cualquier IP que acceda a ese archivo se bloquea automáticamente.

2. **Falsos positivos**: Si usuarios legítimos son bloqueados, puedes:
   - Eliminar su IP de `blocked_ips.txt`
   - Reducir la sensibilidad de los patrones

3. **Backup**: Haz backup de `blocked_ips.txt` periódicamente

---

## 📈 Resultados Esperados

Con estos cambios, deberías ver:
- ✅ **90%+ reducción** en intentos de bots exitosos
- ✅ **Bloqueo automático** de IPs sospechosas en 3 intentos
- ✅ **Detección temprana** de automatización en el frontend
- ✅ **Logs detallados** para análisis

---

## 🆘 Soporte

Si los bots continúan:
1. Comparte nuevos logs de Telegram
2. Revisa `blocked_log.txt` para ver qué patrones están pasando
3. Considera activar reCAPTCHA como última medida
