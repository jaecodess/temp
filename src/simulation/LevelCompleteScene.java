package simulation;

import engine.managers.InputOutputManager;
import engine.scene.Scene;

/**
 * Level Complete interstitial screen.
 * Shown for a few seconds between levels, then auto-advances.
 * Player can also press ENTER to skip.
 *
 * Uses the Scene lifecycle cleanly — no game logic, pure display.
 */
public class LevelCompleteScene extends Scene {

    private final InputOutputManager inputManager;

    // Data injected by PlayableGame before loading this scene
    private int   score;
    private int   levelNumber;
    private String nextLevelName;
    private float displayTimer;
    private static final float DISPLAY_SECONDS = 3.5f;

    // Callback flag — PlayableGame polls this
    private boolean advanceRequested = false;

    public LevelCompleteScene(InputOutputManager io) {
        super("LevelComplete");
        this.inputManager = io;
    }

    /** Call this before loading the scene to inject display data. */
    public void prepare(int score, int levelNumber, String nextLevelName) {
        this.score         = score;
        this.levelNumber   = levelNumber;
        this.nextLevelName = nextLevelName;
        this.displayTimer  = DISPLAY_SECONDS;
        this.advanceRequested = false;
    }

    // ── Scene lifecycle ──────────────────────────────────────────────
    @Override public void start()  { isRunning = true; advanceRequested = false; }
    @Override public void stop()   { isRunning = false; }
    @Override public void render() { /* painting done in PlayableGame.paintComponent */ }
    @Override public void update(float dt) {
        if (!isRunning) return;
        displayTimer -= dt;
        if (displayTimer <= 0) advanceRequested = true;
    }
    @Override protected void loadAssets()  { }
    @Override protected void handleInput() { }
    @Override protected void draw()        { }

    // ── Accessors ────────────────────────────────────────────────────
    public boolean isAdvanceRequested() { return advanceRequested; }
    public void    requestAdvance()     { advanceRequested = true; }
    public float   getTimeRemaining()   { return Math.max(0, displayTimer); }
    public int     getScore()           { return score; }
    public int     getLevelNumber()     { return levelNumber; }
    public String  getNextLevelName()   { return nextLevelName; }
}
