package engine.scene;

import engine.entities.*;
import engine.managers.*;

/**
 * Main game scene - contains the actual gameplay
 */
public class MainScene extends Scene {
    private EntityManager entityManager;
    private MovementManager movementManager;
    private CollisionManager collisionManager;
    private InputOutputManager inputManager;
    private Player player;
    private boolean gameOver;
    private float gameTime;
    
    public MainScene(EntityManager entityManager, 
                     MovementManager movementManager,
                     CollisionManager collisionManager,
                     InputOutputManager inputManager) {
        super("MainScene");
        this.entityManager = entityManager;
        this.movementManager = movementManager;
        this.collisionManager = collisionManager;
        this.inputManager = inputManager;
        this.gameOver = false;
        this.gameTime = 0;
    }
    
    @Override
    public void start() {
        System.out.println("MainScene: Starting game...");
        isRunning = true;
        gameOver = false;
        gameTime = 0;
        loadAssets();
        inputManager.getSpeaker().play("background", 0.3f);
    }
    
    public void setGameOver(boolean gameOver) {
        this.gameOver = gameOver;
        if (gameOver) {
            inputManager.getSpeaker().stop("background");
        }
    }
    
    public void reset() {
        gameOver = false;
        gameTime = 0;
        System.out.println("MainScene: Reset");
    }
    
    public void setPlayer(Player player) {
        this.player = player;
    }
    
    @Override
    public void stop() {
        System.out.println("MainScene: Stopping game...");
        isRunning = false;
        inputManager.getSpeaker().stop("background");
    }
    
    @Override
    protected void loadAssets() {
        System.out.println("MainScene: Loading game assets (using beep)...");
    }
    
    @Override
    protected void handleInput() {
        if (!isRunning || gameOver) return;
    }
    
    @Override
    protected void draw() {
        if (!isRunning) return;
        
        // Render game world
        System.out.println("\n=== GAME WORLD ===");
        System.out.println("Time: " + String.format("%.1f", gameTime) + "s");
        System.out.println("Player: " + player.toString());
        System.out.println("Active Entities: " + entityManager.getActiveEntityCount());
        
        // Render all entities
        entityManager.renderAll();
        
        if (gameOver) {
            System.out.println("*** GAME OVER ***");
        }
        System.out.println("==================\n");
    }
    
    @Override
    public void update(float dt) {
        if (!isRunning) return;
        
        gameTime += dt;
        handleInput();
        
        if (!gameOver) {
            movementManager.update(dt);
            entityManager.updateAll(dt);
            collisionManager.detectCollisions();
        }
    }
    
    @Override
    public void render() {
        if (!isRunning) return;
        
        draw();
    }
    

    /**
     * Check if game is over
     */
    public boolean isGameOver() {
        return gameOver;
    }
    
    /**
     * Get current game time
     */
    public float getGameTime() {
        return gameTime;
    }
    
    /**
     * Get player entity
     */
    public Player getPlayer() {
        return player;
    }
}
