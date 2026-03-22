package engine.scene;

import engine.managers.InputOutputManager;
import engine.input.InputAction;

/**
 * End scene - displays game over screen and results
 */
public class EndScene extends Scene {
    private InputOutputManager inputManager;
    private float finalScore;
    private float finalTime;
    private boolean restartRequested;
    private boolean exitRequested;
    
    public EndScene(InputOutputManager inputManager) {
        super("EndScene");
        this.inputManager = inputManager;
        this.finalScore = 0;
        this.finalTime = 0;
        this.restartRequested = false;
        this.exitRequested = false;
    }
    
    @Override
    public void start() {
        System.out.println("EndScene: Starting...");
        isRunning = true;
        loadAssets();
        restartRequested = false;
        exitRequested = false;
    }
    
    @Override
    public void stop() {
        System.out.println("EndScene: Stopping...");
        isRunning = false;
    }
    
    @Override
    protected void loadAssets() {
        System.out.println("EndScene: Loading end screen assets (using beep)...");
    }
    
    @Override
    protected void handleInput() {
        if (!isRunning) return;
        
        // Check for restart
        if (inputManager.isPressed(InputAction.CONFIRM)) {
            restartRequested = true;
            System.out.println("EndScene: Restart requested");
        }
        
        // Check for exit
        if (inputManager.isPressed(InputAction.CANCEL)) {
            exitRequested = true;
            System.out.println("EndScene: Exit to menu requested");
        }
    }
    
    @Override
    protected void draw() {
        if (!isRunning) return;
        
        drawResults();
    }
    
    /**
     * Draw game results
     */
    public void drawResults() {
        System.out.println("\n╔═══════════════════════════╗");
        System.out.println("║   SIMULATION ENDED!       ║");
        System.out.println("╠═══════════════════════════╣");
        System.out.println("║  Final Score: " + String.format("%-11.0f", finalScore) + "║");
        System.out.println("║  Time Played: " + String.format("%-11.1f", finalTime) + "s║");
        System.out.println("╠═══════════════════════════╣");
        System.out.println("║  Press ENTER to Restart   ║");
        System.out.println("║  Press CANCEL to Exit     ║");
        System.out.println("╚═══════════════════════════╝\n");
    }
    
    @Override
    public void update(float dt) {
        if (!isRunning) return;
        
        handleInput();
        // Update animations, particles, etc.
    }
    
    @Override
    public void render() {
        if (!isRunning) return;
        
        draw();
    }
    
    /**
     * Set game results to display
     */
    public void setResults(float score, float time) {
        this.finalScore = score;
        this.finalTime = time;
    }
    
    /**
     * Check if restart was requested
     */
    public boolean isRestartRequested() {
        return restartRequested;
    }
    
    /**
     * Check if exit was requested
     */
    public boolean isExitRequested() {
        return exitRequested;
    }
    
    /**
     * Reset request flags
     */
    public void resetRequests() {
        restartRequested = false;
        exitRequested = false;
    }

    /**
     * Get results as [score, time]
     */
    public float[] getResults() {
        return new float[]{finalScore, finalTime};
    }
}
