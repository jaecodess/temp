package engine.scene;

import engine.managers.InputOutputManager;
import engine.input.InputAction;

/**
 * Menu scene - displays game menu and handles menu navigation
 */
public class MenuScene extends Scene {
    private InputOutputManager inputManager;
    private String[] menuOptions;
    private int selectedOption;
    private boolean transitionRequested;
    private int lastSelectedOption;
    
    public MenuScene(InputOutputManager inputManager) {
        super("MenuScene");
        this.inputManager = inputManager;
        this.menuOptions = new String[]{"Start Game", "Options", "Exit"};
        this.selectedOption = 0;
        this.lastSelectedOption = -1;
        this.transitionRequested = false;
    }
    
    @Override
    public void start() {
        System.out.println("MenuScene: Starting...");
        isRunning = true;
        loadAssets();
        selectedOption = 0;
        transitionRequested = false;
    }
    
    @Override
    public void stop() {
        System.out.println("MenuScene: Stopping...");
        isRunning = false;
    }
    
    @Override
    protected void loadAssets() {
        System.out.println("MenuScene: Loading menu assets (using beep)...");
    }
    
    @Override
    protected void handleInput() {
        if (!isRunning) return;
        
        // Navigation
        if (inputManager.isPressed(InputAction.MOVE_UP)) {
            selectedOption = (selectedOption - 1 + menuOptions.length) % menuOptions.length;
            if (selectedOption != lastSelectedOption) {
                inputManager.getSpeaker().play("select", 0.5f);
                lastSelectedOption = selectedOption;
            }
        }
        if (inputManager.isPressed(InputAction.MOVE_DOWN)) {
            selectedOption = (selectedOption + 1) % menuOptions.length;
            if (selectedOption != lastSelectedOption) {
                inputManager.getSpeaker().play("select", 0.5f);
                lastSelectedOption = selectedOption;
            }
        }
        
        // Selection
        if (inputManager.isPressed(InputAction.CONFIRM)) {
            inputManager.getSpeaker().play("start", 0.7f);
            handleSelection();
        }
    }
    
    /**
     * Handle menu option selection
     */
    private void handleSelection() {
        System.out.println("MenuScene: Selected option: " + menuOptions[selectedOption]);
        
        switch (selectedOption) {
            case 0: // Start Game
                transitionRequested = true;
                break;
            case 1: // Options
                System.out.println("MenuScene: Opening options (not implemented)");
                break;
            case 2: // Exit
                System.out.println("MenuScene: Exit requested");
                break;
        }
    }
    
    @Override
    protected void draw() {
        if (!isRunning) return;
        
        // Draw menu UI
        System.out.println("\n=== MAIN MENU ===");
        for (int i = 0; i < menuOptions.length; i++) {
            String prefix = (i == selectedOption) ? "> " : "  ";
            System.out.println(prefix + menuOptions[i]);
        }
        System.out.println("================\n");
    }
    
    @Override
    public void update(float dt) {
        if (!isRunning) return;
        
        handleInput();
        // Update menu animations, transitions, etc.
    }
    
    @Override
    public void render() {
        if (!isRunning) return;
        
        draw();
    }
    
    /**
     * Check if transition to main game is requested
     */
    public boolean isTransitionRequested() {
        return transitionRequested;
    }
    
    /**
     * Reset transition flag
     */
    public void resetTransition() {
        transitionRequested = false;
    }
}
