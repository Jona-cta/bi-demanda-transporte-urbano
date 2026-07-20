@echo off
REM ===========================================================================
REM  Modulo de Analisis Inteligente de Demanda
REM  Proyecto Final - Inteligencia de Negocios (UTP)
REM
REM  Levanta el modulo web usando el PHP portatil incluido.
REM  No instala nada en el sistema y no requiere permisos de administrador.
REM ===========================================================================

setlocal
cd /d "%~dp0"

set PUERTO=8010
set PHP=php\php.exe

echo.
echo  ========================================================
echo   Analisis Inteligente de Demanda - Inteligencia de Negocios UTP
echo  ========================================================
echo.

REM --- Comprobaciones antes de arrancar, con mensajes claros ---------------
if not exist "%PHP%" (
  echo  [ERROR] No se encuentra %PHP%
  echo          La carpeta php\ debe viajar junto a este archivo.
  echo.
  pause
  exit /b 1
)

if not exist "data\kpi_datamart.sqlite" (
  echo  [ERROR] Falta el extracto de datos: data\kpi_datamart.sqlite
  echo.
  pause
  exit /b 1
)

if not exist ".env" (
  echo  [AVISO] No existe el archivo .env
  echo.
  echo          El modulo abre igual y muestra los indicadores, pero el
  echo          analisis con IA no funcionara hasta que configures tu clave:
  echo.
  echo            1. Copia  .env.example  como  .env
  echo            2. Escribe tu API Key en GEMINI_API_KEY
  echo               Se obtiene gratis en https://ai.google.dev
  echo.
  pause
)

echo  Servidor iniciado en http://localhost:%PUERTO%
echo.
echo  Abriendo el navegador...
echo  Para detener el servidor, cierra esta ventana.
echo.

start "" "http://localhost:%PUERTO%"

"%PHP%" -c "php\php.ini" -S 127.0.0.1:%PUERTO% -t ia

endlocal
