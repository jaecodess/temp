package simulation;

import engine.entities.*;
import engine.managers.*;
import engine.scene.Scene;

/**
 * Abstract base for all game levels.
 * Each level defines its own speed, score threshold, and background theme.
 * Follows Template Method pattern — subclasses override getConfig().
 */
public abstract class LevelScene extends Scene {
    protected EntityManager entityManager;
    protected MovementManager movementManager;
    protected CollisionManager collisionManager;
    protected InputOutputManager inputManager;
    protected Player player;
    private boolean gameOver;

    public LevelScene(String sceneName,
                      EntityManager em, MovementManager mm,
                      CollisionManager cm, InputOutputManager io) {
        super(sceneName);
        this.entityManager  = em;
        this.movementManager = mm;
        this.collisionManager = cm;
        this.inputManager   = io;
        this.gameOver       = false;
    }

    /** Config bundle every concrete level must supply. */
    public abstract LevelConfig getConfig();

    // ── Scene lifecycle ──────────────────────────────────────────────
    @Override public void start() {
        isRunning = true;
        gameOver  = false;
        inputManager.getSpeaker().play("background", 0.3f);
        System.out.println("Starting level: " + sceneName);
    }

    @Override public void stop() {
        isRunning = false;
        inputManager.getSpeaker().stop("background");
    }

    @Override public void update(float dt) {
        if (!isRunning) return;
        if (!gameOver) {
            movementManager.update(dt);
            entityManager.updateAll(dt);
            collisionManager.detectCollisions();
        }
    }

    @Override public void render() { }
    @Override protected void loadAssets() { }
    @Override protected void handleInput() { }
    @Override protected void draw() { }

    // ── Helpers ──────────────────────────────────────────────────────
    public void setPlayer(Player p)       { this.player = p; }
    public Player getPlayer()             { return player; }
    public boolean isGameOver()           { return gameOver; }
    public void setGameOver(boolean v)    {
        this.gameOver = v;
        if (v) inputManager.getSpeaker().stop("background");
    }
    public void reset()                   { gameOver = false; }
}
