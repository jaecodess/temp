@echo off
echo === Building Nutrition Quest ===
if not exist out mkdir out
dir /s /b src\*.java > src\files.txt
javac -d out -sourcepath src @src\files.txt
if %errorlevel% == 0 (
    echo Build successful!
    echo.
    echo Run the game with:
    echo   cd out ^&^& java simulation.PlayableGame
) else (
    echo Build FAILED - check errors above
)
