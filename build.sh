#!/bin/bash
echo "=== Building Nutrition Quest ==="
mkdir -p out
find src -name "*.java" > src/files.txt
javac -d out -sourcepath src $(cat src/files.txt)
if [ $? -eq 0 ]; then
    echo "Build successful!"
    echo ""
    echo "Run the game with:"
    echo "  cd out && java simulation.PlayableGame"
else
    echo "Build FAILED - check errors above"
fi
