package simulation;

import engine.core.GameMaster;
import engine.entities.Player;
import engine.entities.Entity;
import engine.entities.TextureObject;
import engine.managers.*;
import engine.input.InputAction;
import engine.collision.ICollisionListener;
import javax.swing.*;
import java.awt.*;
import java.awt.event.*;
import java.lang.reflect.InvocationTargetException;
import java.util.ArrayList;
import java.util.List;
import java.util.Random;

/**
 * Nutrition Quest — Main game controller.
 * Three levels (Kitchen → Market → Jungle), each faster than the last.
 * Good food respawns on collection. Bad food triggers HP loss + quiz.
 * Backgrounds driven by active LevelScene via SceneManager.
 */
public class PlayableGame extends JPanel implements KeyListener, Runnable {
    private static final int WINDOW_WIDTH  = 800;
    private static final int WINDOW_HEIGHT = 600;
    private static final String TITLE = "Nutrition Quest";

    // ── Engine managers ──────────────────────────────────────────────
    private GameMaster         gameMaster;
    private SceneManager       sceneManager;
    private EntityManager      entityManager;
    private InputOutputManager inputManager;
    private MovementManager    movementManager;
    private CollisionManager   collisionManager;
    private TimeManager        timeManager;
    private Player             player;

    // ── Scenes ───────────────────────────────────────────────────────
    private engine.scene.MenuScene menuScene;
    private engine.scene.EndScene  endScene;
    private KitchenScene           kitchenScene;
    private MarketScene            marketScene;
    private JungleScene            jungleScene;
    private LevelScene             currentLevelScene;
    private LevelCompleteScene     levelCompleteScene;

    // ── Entity lists ─────────────────────────────────────────────────
    private List<FoodItem> goodFoods = new ArrayList<>();
    private List<FoodItem> badFoods  = new ArrayList<>();

    // ── Loop / state ─────────────────────────────────────────────────
    private boolean running, paused;
    private Thread  gameThread;
    private int     score = 0, highScore = 0;
    private float   gameTime = 0;
    private long    lastTime, fpsTimer = 0;
    private int     fps = 0, frameCount = 0;

    // ── Health ───────────────────────────────────────────────────────
    private int   playerHealth            = 100;
    private static final int MAX_HEALTH         = 100;
    private static final int HP_LOSS_BAD_FOOD   = 20;
    private static final int HP_RESTORE_CORRECT = 5;
    // Brief invincibility after taking a hit so the player can't be
    // immediately hit again while still overlapping the spawn point
    private float invincibilityTimer = 0f;
    private static final float INVINCIBILITY_DURATION = 5.0f;

    // ── Level-up banner ──────────────────────────────────────────────
    private float  levelUpBannerTimer = 0f;
    private String levelUpBannerText  = "";

    // ── Speed ────────────────────────────────────────────────────────
    private float badFoodBaseSpeed = 75f;

    // ── Powerups ─────────────────────────────────────────────────────
    private int     powerupsRedeemed  = 0;
    private static final int[] POWERUP_THRESHOLDS = { 8, 20, 40 };
    private boolean powerupAvailable  = false;
    private String  activePowerup     = null;
    private float   powerupTimer      = 0f;
    private boolean shieldActive      = false;
    private boolean freezeActive      = false;
    private boolean doublePointsActive = false;

    // ── Quiz bank ────────────────────────────────────────────────────
    private static final String[][] QUIZ = {
        {"Which vitamin is abundant in oranges?",   "Vitamin A","Vitamin C","Vitamin D","Vitamin K","1"},
        {"Which food is a good source of protein?", "Candy","Chicken","Soda","Chips","1"},
        {"What is a healthy breakfast choice?",     "Donut","Oatmeal","Fries","Cookie","1"},
        {"Which helps build strong bones?",         "Sugar","Calcium","Salt","Oil","1"},
        {"Which is a whole grain?",                 "White bread","Brown rice","Cake","Candy","1"},
        {"What should you drink most of?",          "Soda","Energy drink","Water","Milkshake","2"},
        {"Which food is high in fiber?",            "Ice cream","Broccoli","Candy","Chips","1"},
        {"What nutrient do carrots provide?",       "Protein","Vitamin A","Fat","Sugar","1"},
        {"Which is a healthy snack?",               "Apple","Cookie","Candy bar","Cake","0"},
        {"What does iron help with?",               "Taste","Blood health","Smell","Hair color","1"}
    };
    private int quizIndex = 0;

    // ── Menu ─────────────────────────────────────────────────────────
    private int      menuSelection = 0;
    private String[] menuOptions   = {"Start Game","How to Play","Exit"};
    private long     lastKeyPress  = 0;
    private static final long KEY_COOLDOWN = 200;

    // ── Food pools ───────────────────────────────────────────────────
    private static final String[] GOOD_NAMES = {"Apple","Banana","Broccoli","Carrot","Orange","Grapes","Spinach","Tomato"};
    private static final String[] GOOD_BEH   = {"wander","patrol","wander","patrol","wander","wander","patrol","wander"};
    private static final String[] BAD_NAMES  = {"Burger","Soda","Chips","Candy","Pizza"};
    private static final String[] BAD_BEH    = {"patrol","wander","patrol","wander","patrol"};

    // ════════════════════════════════════════════════════════════════
    public PlayableGame() {
        setPreferredSize(new Dimension(WINDOW_WIDTH, WINDOW_HEIGHT));
        setBackground(Color.BLACK);
        setFocusable(true);
        addKeyListener(this);
        initEngine();
        initScenes();
    }

    private void initEngine() {
        gameMaster       = new GameMaster(); gameMaster.initialize();
        sceneManager     = gameMaster.getSceneManager();
        entityManager    = gameMaster.getEntityManager();
        inputManager     = gameMaster.getInputOutputManager();
        movementManager  = gameMaster.getMovementManager();
        collisionManager = gameMaster.getCollisionManager();
        timeManager      = gameMaster.getTimeManager();
        inputManager.bindAction(InputAction.CONFIRM, KeyEvent.VK_ENTER);
        inputManager.bindAction(InputAction.ACTION_2, KeyEvent.VK_Q);
    }

    private void initScenes() {
        menuScene    = new engine.scene.MenuScene(inputManager);
        endScene     = new engine.scene.EndScene(inputManager);
        kitchenScene = new KitchenScene(entityManager, movementManager, collisionManager, inputManager);
        marketScene  = new MarketScene (entityManager, movementManager, collisionManager, inputManager);
        jungleScene  = new JungleScene (entityManager, movementManager, collisionManager, inputManager);
        levelCompleteScene = new LevelCompleteScene(inputManager);
        sceneManager.addScene("MenuScene",      menuScene);
        sceneManager.addScene("EndScene",       endScene);
        sceneManager.addScene("LevelComplete",  levelCompleteScene);
        sceneManager.addScene("Level1",         kitchenScene);
        sceneManager.addScene("Level2",         marketScene);
        sceneManager.addScene("Level3",         jungleScene);
        sceneManager.loadScene("MenuScene");
    }

    // ── New game (level 1) ───────────────────────────────────────────
    private void startNewGame() {
        paused = false; score = 0; gameTime = 0; playerHealth = MAX_HEALTH;
        quizIndex = 0; powerupAvailable = false; powerupsRedeemed = 0; invincibilityTimer = 0f;
        activePowerup = null; shieldActive = false; freezeActive = false; doublePointsActive = false;
        timeManager.reset();
        timeManager.resume(); // un-pause in case we came from EndScene
        kitchenScene.reset(); marketScene.reset(); jungleScene.reset();
        startLevel(kitchenScene);
    }

    private void startLevel(LevelScene level) {
        entityManager.clear(); collisionManager.clearAll();
        goodFoods.clear(); badFoods.clear();

        // Clear all keyboard state so no "held" keys carry over into the new level
        inputManager.getKeyboard().clearAll();

        // Remove all stale movement entities (old players from previous levels)
        for (engine.entities.Entity old : new ArrayList<>(movementManager.getEntities())) {
            movementManager.removeEntity(old);
        }

        currentLevelScene = level;
        LevelConfig cfg   = level.getConfig();
        badFoodBaseSpeed  = cfg.badFoodSpeed;

        float px = WINDOW_WIDTH/2f, py = WINDOW_HEIGHT/2f;
        player = new Player(px, py, "Player");
        player.setSpeed(220f); player.setWidth(36); player.setHeight(36);
        entityManager.addEntity(player);
        movementManager.addEntity(player);
        collisionManager.addCollidable(player, "player");
        level.setPlayer(player);

        Random rnd = new Random(); int pad = 70;
        for (int i = 0; i < GOOD_NAMES.length; i++) {
            float x, y;
            do { x = pad + rnd.nextFloat()*(WINDOW_WIDTH -2*pad);
                 y = pad + rnd.nextFloat()*(WINDOW_HEIGHT-2*pad);
            } while (dist(x,y,px,py) < 110f);
            spawnGood(x, y, GOOD_BEH[i], GOOD_NAMES[i], cfg.goodFoodSpeed);
        }
        for (int i = 0; i < BAD_NAMES.length; i++) {
            float x, y;
            do { x = pad + rnd.nextFloat()*(WINDOW_WIDTH -2*pad);
                 y = pad + rnd.nextFloat()*(WINDOW_HEIGHT-2*pad);
            } while (dist(x,y,px,py) < 110f);
            spawnBad(x, y, BAD_BEH[i], BAD_NAMES[i], badFoodBaseSpeed);
        }
        createWalls();
        wirePlayerRefs();
        setupCollisions();
        sceneManager.loadScene(level.getSceneName());
    }

    private void advanceToLevel(LevelScene next, String banner) {
        entityManager.clear(); collisionManager.clearAll();
        goodFoods.clear(); badFoods.clear();

        // Clear keyboard so held keys don't carry into the new level
        inputManager.getKeyboard().clearAll();

        // Remove stale player references from the movement manager
        for (engine.entities.Entity old : new ArrayList<>(movementManager.getEntities())) {
            movementManager.removeEntity(old);
        }

        next.reset();
        LevelConfig cfg  = next.getConfig();
        badFoodBaseSpeed = cfg.badFoodSpeed;

        float px = WINDOW_WIDTH/2f, py = WINDOW_HEIGHT/2f;
        player = new Player(px, py, "Player");
        player.setSpeed(220f); player.setWidth(36); player.setHeight(36);
        entityManager.addEntity(player);
        movementManager.addEntity(player);
        collisionManager.addCollidable(player, "player");
        next.setPlayer(player);

        Random rnd = new Random(); int pad = 70;
        for (int i = 0; i < GOOD_NAMES.length; i++) {
            float x, y;
            do { x = pad + rnd.nextFloat()*(WINDOW_WIDTH -2*pad);
                 y = pad + rnd.nextFloat()*(WINDOW_HEIGHT-2*pad);
            } while (dist(x,y,px,py) < 110f);
            spawnGood(x, y, GOOD_BEH[i], GOOD_NAMES[i], cfg.goodFoodSpeed);
        }
        for (int i = 0; i < BAD_NAMES.length; i++) {
            float x, y;
            do { x = pad + rnd.nextFloat()*(WINDOW_WIDTH -2*pad);
                 y = pad + rnd.nextFloat()*(WINDOW_HEIGHT-2*pad);
            } while (dist(x,y,px,py) < 110f);
            spawnBad(x, y, BAD_BEH[i], BAD_NAMES[i], badFoodBaseSpeed);
        }
        createWalls();
        wirePlayerRefs();
        setupCollisions();
        currentLevelScene  = next;
        levelUpBannerText  = banner;
        levelUpBannerTimer = 2.5f;
        // Give the player breathing room to orient in the new level
        invincibilityTimer = INVINCIBILITY_DURATION;
        sceneManager.loadScene(next.getSceneName());
    }

    // ── Entity spawn ─────────────────────────────────────────────────
    private void spawnGood(float x, float y, String beh, String name, float speed) {
        FoodItem f = new FoodItem(x, y, beh, name, true);
        f.setWidth(56); f.setHeight(56); f.setSpeed(speed);
        f.setWorldBounds(WINDOW_WIDTH, WINDOW_HEIGHT);
        goodFoods.add(f); entityManager.addEntity(f); collisionManager.addCollidable(f,"good_food");
    }
    private void spawnBad(float x, float y, String beh, String name, float speed) {
        FoodItem f = new FoodItem(x, y, beh, name, false);
        f.setWidth(56); f.setHeight(56); f.setSpeed(speed);
        f.setWorldBounds(WINDOW_WIDTH, WINDOW_HEIGHT);
        badFoods.add(f); entityManager.addEntity(f); collisionManager.addCollidable(f,"bad_food");
    }

    /** Give every bad food a reference to the current player for homing. */
    private void wirePlayerRefs() {
        for (FoodItem bf : badFoods) bf.setPlayerRef(player);
    }

    private void spawnWall(float x, float y, float w, float h) {
        TextureObject wall = new TextureObject("wall", x, y, 1.0f);
        wall.setWidth(w); wall.setHeight(h);
        entityManager.addEntity(wall);
        collisionManager.addCollidable(wall, "wall");
    }

    /** Place a fixed set of walls for every level. Same layout each time. */
    private void createWalls() {
        // Central island cluster
        spawnWall(200, 200, 80, 24);
        spawnWall(400, 160, 24, 80);
        spawnWall(580, 220, 80, 24);
        // Mid-screen barriers
        spawnWall(140, 360, 24, 90);
        spawnWall(640, 340, 24, 90);
        spawnWall(340, 440, 110, 24);
        // Corner caps
        spawnWall(100, 130, 60, 20);
        spawnWall(640, 130, 60, 20);
        spawnWall(100, 490, 60, 20);
        spawnWall(640, 490, 60, 20);
    }
    private float dist(float x1,float y1,float x2,float y2){float dx=x1-x2,dy=y1-y2;return(float)Math.sqrt(dx*dx+dy*dy);}

    // ── Collisions ───────────────────────────────────────────────────
    private void setupCollisions() {
        collisionManager.registerHandler("player","good_food", new ICollisionListener() {
            @Override public void onCollision(Entity a, Entity b) {
                if (!(b instanceof FoodItem)) return;
                FoodItem f = (FoodItem) b;
                if (!f.isGoodFood() || !f.isActive()) return;
                score += doublePointsActive ? 2 : 1;
                inputManager.getSpeaker().beep();
                f.setActive(false); entityManager.removeEntity(f); collisionManager.removeCollidable(f); goodFoods.remove(f);
                // Respawn immediately
                LevelConfig cfg = currentLevelScene.getConfig();
                Random rnd = new Random(); int pad = 70, idx = rnd.nextInt(GOOD_NAMES.length);
                float nx, ny;
                do { nx = pad+rnd.nextFloat()*(WINDOW_WIDTH-2*pad); ny = pad+rnd.nextFloat()*(WINDOW_HEIGHT-2*pad);
                } while (dist(nx,ny,player.getX(),player.getY())<110f);
                spawnGood(nx, ny, GOOD_BEH[idx], GOOD_NAMES[idx], cfg.goodFoodSpeed);            }
        });
        collisionManager.registerHandler("player","bad_food", new ICollisionListener() {
            @Override public void onCollision(Entity a, Entity b) {
                if (!(b instanceof FoodItem)) return;
                FoodItem f = (FoodItem) b;
                if (f.isGoodFood() || !f.isActive()) return;
                // Don't take damage during invincibility window
                if (invincibilityTimer > 0) return;
                if (shieldActive) { shieldActive = false; inputManager.getSpeaker().beep(); return; }
                playerHealth -= HP_LOSS_BAD_FOOD;
                if (playerHealth <= 0) { playerHealth=0; currentLevelScene.setGameOver(true); return; }
                handleBadFoodTouch(f);
            }
        });

        // Walls push any entity out on overlap — pure AABB, always moves the non-wall entity
        ICollisionListener wallPush = new ICollisionListener() {
            @Override public void onCollision(Entity a, Entity b) {
                // Determine which is the wall and which is the mover
                // HandleCollision registers both directions, so a or b could be the wall
                Entity mover = (b instanceof TextureObject) ? a : b;
                Entity wall  = (b instanceof TextureObject) ? b : a;

                float dx  = mover.getX() - wall.getX();
                float dy  = mover.getY() - wall.getY();
                float ovX = (mover.getWidth()/2  + wall.getWidth()/2)  - Math.abs(dx);
                float ovY = (mover.getHeight()/2 + wall.getHeight()/2) - Math.abs(dy);
                if (ovX <= 0 || ovY <= 0) return;

                // Minimum translation vector — push mover out along smallest overlap axis
                if (ovX < ovY) {
                    float push = (dx == 0 ? 1 : Math.signum(dx)) * ovX;
                    mover.setPosition(mover.getX() + push, mover.getY());
                } else {
                    float push = (dy == 0 ? 1 : Math.signum(dy)) * ovY;
                    mover.setPosition(mover.getX(), mover.getY() + push);
                }
            }
        };
        collisionManager.registerHandler("player","wall", wallPush);
        collisionManager.registerHandler("good_food","wall", wallPush);
        collisionManager.registerHandler("bad_food","wall", wallPush);
    }

    private void handleBadFoodTouch(FoodItem f) {
        f.setActive(false); entityManager.removeEntity(f); collisionManager.removeCollidable(f); badFoods.remove(f);

        // ── 1. Pause all bad-food movement while quiz is shown ────────
        for (FoodItem bf : badFoods) bf.setSpeed(0f);

        // ── 2. Clear keyboard so no keys appear held after dialog ─────
        inputManager.getKeyboard().clearAll();

        // ── 3. Show quiz on EDT, block game thread until answered ─────
        String[] q = QUIZ[quizIndex % QUIZ.length]; quizIndex++;
        String[] opts = {q[1],q[2],q[3],q[4]}; int correct = Integer.parseInt(q[5]);
        Frame frm = SwingUtilities.getWindowAncestor(this) instanceof Frame
                    ? (Frame)SwingUtilities.getWindowAncestor(this)
                    : (Frame.getFrames().length > 0 ? Frame.getFrames()[0] : null);
        final boolean[] res = {false};
        try {
            if (frm != null) {
                final Frame pf = frm;
                SwingUtilities.invokeAndWait(() -> res[0] = QuizDialog.showQuiz(pf, q[0], opts, correct));
            }
        } catch (InterruptedException | InvocationTargetException ex) { res[0] = false; }

        // ── 4. Clear keys AGAIN — dialog may have left keys "stuck" ──
        inputManager.getKeyboard().clearAll();

        // ── 5. Teleport player to centre, stop its velocity ───────────
        float cx = WINDOW_WIDTH / 2f, cy = WINDOW_HEIGHT / 2f;
        if (player != null) {
            player.setPosition(cx, cy);
            player.getVelocity().set(0, 0);
        }

        // ── 6. Grant invincibility so player can't be hit mid-warp ───
        invincibilityTimer = INVINCIBILITY_DURATION;

        // ── 7. Apply quiz result ──────────────────────────────────────
        if (res[0]) {
            score += doublePointsActive ? 2 : 1;
            playerHealth = Math.min(MAX_HEALTH, playerHealth + HP_RESTORE_CORRECT);
            inputManager.getSpeaker().beep();
        } else {
            playerHealth -= 10;
            if (playerHealth <= 0) { playerHealth = 0; currentLevelScene.setGameOver(true); return; }
        }

        // ── 8. Restore bad food speed then bump it up ─────────────────
        LevelConfig cfg = currentLevelScene.getConfig();
        badFoodBaseSpeed += cfg.badFoodSpeedIncrement;
        for (FoodItem bf : new ArrayList<>(badFoods)) bf.setSpeed(badFoodBaseSpeed);

        // ── 9. Respawn new bad food — guaranteed far from centre ──────
        Random rnd = new Random();
        int pad = 80; float nx, ny;
        do {
            nx = pad + rnd.nextFloat() * (WINDOW_WIDTH  - 2 * pad);
            ny = pad + rnd.nextFloat() * (WINDOW_HEIGHT - 2 * pad);
        } while (dist(nx, ny, cx, cy) < 180f);          // at least 180 px away
        int idx = rnd.nextInt(BAD_NAMES.length);
        spawnBad(nx, ny, BAD_BEH[idx], BAD_NAMES[idx], badFoodBaseSpeed);
        if (!badFoods.isEmpty()) badFoods.get(badFoods.size()-1).setPlayerRef(player);
    }

    // ── Powerup ──────────────────────────────────────────────────────
    private void showPowerupDialog() {
        String[] opts={"Freeze bad food 5s","Shield: block 1 hit","Double Points 10s"};
        int c=JOptionPane.showOptionDialog(this,"Choose a powerup!","Powerup!",
            JOptionPane.DEFAULT_OPTION,JOptionPane.INFORMATION_MESSAGE,null,opts,opts[0]);
        if (c<0) return;
        powerupAvailable=false; powerupsRedeemed++;
        activePowerup = c==0?"freeze":(c==1?"shield":"double");
        powerupTimer  = c==0?5f:(c==2?10f:0f);
        if (c==0) freezeActive=true; if (c==1) shieldActive=true; if (c==2) doublePointsActive=true;
        inputManager.getSpeaker().beep();
    }

    // ── Game loop ────────────────────────────────────────────────────
    public void start() { if(running)return; running=true; gameThread=new Thread(this); gameThread.start(); }

    @Override public void run() {
        lastTime=System.nanoTime(); fpsTimer=System.currentTimeMillis();
        while (running) {
            long now=System.nanoTime(); float rawDt=(now-lastTime)/1_000_000_000.0f; lastTime=now;
            timeManager.update(rawDt); float dt=timeManager.getDeltaTime();
            inputManager.pollInput(); sceneManager.update(dt); inputManager.processOutput();
            updateState(dt); checkTransitions(); repaint();
            frameCount++; if(System.currentTimeMillis()-fpsTimer>=1000){fps=frameCount;frameCount=0;fpsTimer=System.currentTimeMillis();}
            try{Thread.sleep(16);}catch(InterruptedException ignored){}
        }
    }

    private void updateState(float dt) {
        engine.scene.Scene cur=sceneManager.getActiveScene(); if(cur==null)return;
        String n=cur.getSceneName();
        if(!"Level1".equals(n)&&!"Level2".equals(n)&&!"Level3".equals(n))return;        if(currentLevelScene==null||currentLevelScene.isGameOver())return;
        gameTime=timeManager.getTotalTime(); keepInBounds();
        // Tick invincibility window
        if (invincibilityTimer > 0) invincibilityTimer -= dt;
        if(powerupTimer>0){powerupTimer-=dt;if(powerupTimer<=0){if("freeze".equals(activePowerup))freezeActive=false;if("double".equals(activePowerup))doublePointsActive=false;activePowerup=null;}}
        for(FoodItem bf:badFoods) bf.setSpeed(freezeActive?0f:badFoodBaseSpeed);
        if(powerupsRedeemed<POWERUP_THRESHOLDS.length&&score>=POWERUP_THRESHOLDS[powerupsRedeemed])powerupAvailable=true;
        if(levelUpBannerTimer>0)levelUpBannerTimer-=dt;
    }

    private void checkTransitions() {
        engine.scene.Scene cur=sceneManager.getActiveScene(); if(cur==null)return;
        String n=cur.getSceneName();

        if("MenuScene".equals(n)&&menuScene.isTransitionRequested()){
            menuScene.resetTransition(); startNewGame();

        } else if(currentLevelScene!=null && currentLevelScene.isGameOver()){
            gameTime=timeManager.getTotalTime(); if(score>highScore)highScore=score;
            timeManager.pause();   // stop the clock — nothing should tick on the end screen
            endScene.setResults(score,gameTime); sceneManager.loadScene("EndScene");

        } else if("Level1".equals(n) && score>=kitchenScene.getConfig().scoreToAdvance){
            showLevelComplete(1,"Level 2 — The Market");

        } else if("Level2".equals(n) && score>=marketScene.getConfig().scoreToAdvance){
            showLevelComplete(2,"Level 3 — The Jungle");

        } else if("LevelComplete".equals(n) && levelCompleteScene.isAdvanceRequested()){
            // Resume into whichever next level was queued
            if(pendingNextLevel!=null){
                advanceToLevel(pendingNextLevel, pendingNextBanner);
                pendingNextLevel=null; pendingNextBanner=null;
            }

        } else if("EndScene".equals(n)){
            if(endScene.isRestartRequested()){endScene.resetRequests();startNewGame();}
            else if(endScene.isExitRequested()){endScene.resetRequests();sceneManager.loadScene("MenuScene");}
        }
    }

    // ── Pending level (set when showing LevelComplete) ───────────────
    private LevelScene pendingNextLevel  = null;
    private String     pendingNextBanner = null;

    private void showLevelComplete(int completedLevel, String nextName) {
        // Store where to go next; pause the active level scene
        if (completedLevel == 1) { pendingNextLevel = marketScene;  pendingNextBanner = "Level 2 — The Market!"; }
        else                     { pendingNextLevel = jungleScene;  pendingNextBanner = "Level 3 — The Jungle!"; }
        levelCompleteScene.prepare(score, completedLevel, nextName);
        sceneManager.loadScene("LevelComplete");
    }

    private void keepInBounds() {
        if (player == null) return;
        float hw = player.getWidth()  / 2f;
        float hh = player.getHeight() / 2f;
        float minY = 55f + hh;   // 55 = HUD bar height — never go behind it
        if (player.getX() < hw)                player.setX(hw);
        if (player.getX() > WINDOW_WIDTH - hw)  player.setX(WINDOW_WIDTH - hw);
        if (player.getY() < minY)               player.setY(minY);
        if (player.getY() > WINDOW_HEIGHT - hh) player.setY(WINDOW_HEIGHT - hh);
    }

    // ════════════════════════════════════════════════════════════════
    // RENDERING
    // ════════════════════════════════════════════════════════════════
    @Override protected void paintComponent(Graphics g) {
        super.paintComponent(g);
        Graphics2D g2=(Graphics2D)g;
        g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING,   RenderingHints.VALUE_ANTIALIAS_ON);
        g2.setRenderingHint(RenderingHints.KEY_TEXT_ANTIALIASING,RenderingHints.VALUE_TEXT_ANTIALIAS_ON);
        engine.scene.Scene cur=sceneManager.getActiveScene();
        if(cur==null){drawMenu(g2);return;}
        switch(cur.getSceneName()){
            case"MenuScene":drawMenu(g2);break;
            case"Level1":case"Level2":case"Level3":
                drawGame(g2);
                if(paused)drawPaused(g2);
                if(levelUpBannerTimer>0)drawBanner(g2);
                if(currentLevelScene!=null&&currentLevelScene.isGameOver())drawGameOverOverlay(g2);
                break;
            case"LevelComplete": drawLevelComplete(g2); break;
            case"EndScene":drawEndScreen(g2);break;
            default:drawMenu(g2);
        }
    }

    // ── Menu screen ──────────────────────────────────────────────────
    private void drawMenu(Graphics2D g) {
        GradientPaint gp=new GradientPaint(0,0,new Color(15,20,45),0,WINDOW_HEIGHT,new Color(40,15,55));
        g.setPaint(gp); g.fillRect(0,0,WINDOW_WIDTH,WINDOW_HEIGHT);
        g.setColor(new Color(255,255,255,18));
        for(int x=20;x<WINDOW_WIDTH;x+=40)for(int y=20;y<WINDOW_HEIGHT;y+=40)g.fillOval(x-2,y-2,4,4);

        // ── Title block ───────────────────────────────────────────────
        g.setFont(new Font("Arial",Font.BOLD,52)); g.setColor(new Color(255,220,50));
        drawC(g,"NUTRITION QUEST",78);
        g.setFont(new Font("Arial",Font.PLAIN,17)); g.setColor(new Color(180,220,180));
        drawC(g,"Collect healthy food  •  Avoid junk  •  Answer quizzes",110);

        // ── Level preview cards  (y=132 … y=132+65=197) ──────────────
        String[] lvlNames={"Kitchen","Market","Jungle"};
        String[] lvlDesc ={"Slow & steady","Medium pace","Fast chaos"};
        Color[]  lvlCols ={new Color(255,200,100),new Color(100,200,255),new Color(100,220,130)};
        int cw=170, ch=65, gap=16;
        int sx=(WINDOW_WIDTH - 3*cw - 2*gap)/2;
        int cardY=130;
        for(int i=0;i<3;i++){
            int cardX=sx+i*(cw+gap);
            g.setColor(new Color(lvlCols[i].getRed(),lvlCols[i].getGreen(),lvlCols[i].getBlue(),40));
            g.fillRoundRect(cardX,cardY,cw,ch,12,12);
            g.setColor(lvlCols[i]); g.setStroke(new BasicStroke(2));
            g.drawRoundRect(cardX,cardY,cw,ch,12,12);
            // Title
            String title="Lv"+(i+1)+": "+lvlNames[i];
            g.setFont(new Font("Arial",Font.BOLD,13)); g.setColor(lvlCols[i]);
            int tw=g.getFontMetrics().stringWidth(title);
            g.drawString(title, cardX+(cw-tw)/2, cardY+26);
            // Description
            g.setFont(new Font("Arial",Font.PLAIN,12)); g.setColor(new Color(200,200,200));
            int dw=g.getFontMetrics().stringWidth(lvlDesc[i]);
            g.drawString(lvlDesc[i], cardX+(cw-dw)/2, cardY+48);
        }
        // Cards bottom edge = cardY+ch = 130+65 = 195

        // ── Menu buttons  (first top edge = 215, well below 195) ─────
        int btnW=260, btnH=46, btnX=WINDOW_WIDTH/2-btnW/2;
        int btnStartY=215;
        int btnSpacing=62;
        g.setFont(new Font("Arial",Font.BOLD,24));
        for(int i=0;i<menuOptions.length;i++){
            int bTop=btnStartY+i*btnSpacing;
            int bMid=bTop+btnH/2+8; // baseline for centred text
            if(i==menuSelection){
                g.setColor(new Color(255,220,50));
                g.fillRoundRect(btnX,bTop,btnW,btnH,12,12);
                g.setColor(Color.BLACK);
            } else {
                g.setColor(new Color(55,55,75));
                g.fillRoundRect(btnX,bTop,btnW,btnH,12,12);
                g.setColor(Color.WHITE);
            }
            drawC(g,menuOptions[i],bMid);
        }
        // Last button bottom = 215 + 2*62 + 46 = 385

        // ── High score ────────────────────────────────────────────────
        if(highScore>0){
            g.setColor(new Color(100,220,255));
            g.setFont(new Font("Arial",Font.BOLD,17));
            drawC(g,"★ Best: "+highScore, 420);
        }

        // ── Nav hint ──────────────────────────────────────────────────
        g.setColor(new Color(110,110,130));
        g.setFont(new Font("Arial",Font.PLAIN,14));
        drawC(g,"↑↓ navigate   ENTER select",556);
    }

    // ── Game screen ──────────────────────────────────────────────────
    private void drawGame(Graphics2D g) {
        LevelConfig cfg=currentLevelScene!=null?currentLevelScene.getConfig():null;
        if(cfg!=null){
            GradientPaint gp=new GradientPaint(0,0,cfg.bgTop,0,WINDOW_HEIGHT,cfg.bgBottom);
            g.setPaint(gp); g.fillRect(0,0,WINDOW_WIDTH,WINDOW_HEIGHT);
            // Grid in accent colour
            g.setColor(new Color(cfg.accentColor.getRed(),cfg.accentColor.getGreen(),cfg.accentColor.getBlue(),50));
            g.setStroke(new BasicStroke(1));
            for(int x=0;x<WINDOW_WIDTH;x+=50)g.drawLine(x,55,x,WINDOW_HEIGHT);
            for(int y=55;y<WINDOW_HEIGHT;y+=50)g.drawLine(0,y,WINDOW_WIDTH,y);
            drawDecorations(g,cfg);
        }
        // Draw walls (static obstacles)
        for (engine.entities.Entity e : entityManager.getActiveEntities()) {
            if (e instanceof TextureObject) drawWall(g, e);
        }
        for(FoodItem f:goodFoods)if(f!=null&&f.isActive())drawFood(g,f);
        for(FoodItem f:badFoods) if(f!=null&&f.isActive())drawFood(g,f);
        if(player!=null&&player.isActive())drawPlayer(g);
        drawHUD(g);
    }

    private void drawDecorations(Graphics2D g, LevelConfig cfg) {
        switch(cfg.levelNumber){
            case 1: // Kitchen — warm tile grid overlay
                g.setColor(new Color(210,150,70,25));
                for(int x=0;x<WINDOW_WIDTH;x+=100)for(int y=55;y<WINDOW_HEIGHT;y+=100)g.drawRoundRect(x+4,y+4,92,92,8,8);
                break;
            case 2: // Market — clouds and grass strip
                g.setColor(new Color(255,255,255,55));
                int[][]cl={{70,80,80,28},{210,65,95,30},{390,80,65,25},{570,70,85,28},{710,82,55,22}};
                for(int[]c:cl){g.fillOval(c[0],c[1],c[2],c[3]);g.fillOval(c[0]+22,c[1]-12,c[2]-12,c[3]);g.fillOval(c[0]+42,c[1]+2,c[2]-22,c[3]-6);}
                g.setColor(new Color(50,130,50,55));g.fillRect(0,WINDOW_HEIGHT-55,WINDOW_WIDTH,55);
                break;
            case 3: // Jungle — vine stripes and foliage blobs
                g.setColor(new Color(25,90,25,45));
                for(int x=40;x<WINDOW_WIDTH;x+=80)g.fillRect(x-4,55,8,WINDOW_HEIGHT);
                g.setColor(new Color(45,155,40,55));
                Random r=new Random(42);
                for(int i=0;i<28;i++){int rx=r.nextInt(WINDOW_WIDTH),ry=60+r.nextInt(WINDOW_HEIGHT-60),rs=10+r.nextInt(22);g.fillOval(rx,ry,rs,rs);}
                break;
        }
    }

    private void drawHUD(Graphics2D g) {
        g.setColor(new Color(0,0,0,210)); g.fillRect(0,0,WINDOW_WIDTH,52);
        g.setColor(new Color(255,255,255,25)); g.drawLine(0,52,WINDOW_WIDTH,52);

        g.setFont(new Font("Arial",Font.BOLD,17)); g.setColor(Color.WHITE);
        g.drawString("Score: "+score,14,33);

        if(currentLevelScene!=null){
            g.setFont(new Font("Arial",Font.BOLD,14)); g.setColor(new Color(200,230,255));
            drawC(g,currentLevelScene.getConfig().title,32);
            int next=currentLevelScene.getConfig().scoreToAdvance;
            if(next<Integer.MAX_VALUE){g.setFont(new Font("Arial",Font.PLAIN,11));g.setColor(new Color(170,170,170));g.drawString("Next lv: "+score+"/"+next,14,48);}
        }
        // HP bar
        int hbX=WINDOW_WIDTH-220,hbY=12,hbW=170,hbH=20;
        g.setColor(new Color(40,40,40)); g.fillRoundRect(hbX,hbY,hbW,hbH,8,8);
        Color hc=playerHealth>60?new Color(60,210,90):playerHealth>30?new Color(240,180,30):new Color(220,50,50);
        g.setColor(hc); g.fillRoundRect(hbX,hbY,(int)(hbW*playerHealth/(float)MAX_HEALTH),hbH,8,8);
        g.setColor(new Color(255,255,255,100)); g.setStroke(new BasicStroke(1.5f)); g.drawRoundRect(hbX,hbY,hbW,hbH,8,8);
        g.setFont(new Font("Arial",Font.BOLD,12)); g.setColor(Color.WHITE);
        g.drawString("HP "+playerHealth+"/"+MAX_HEALTH,hbX+4,hbY+14);

        if(powerupAvailable){g.setColor(new Color(255,220,50));g.setFont(new Font("Arial",Font.BOLD,13));g.drawString("P = Powerup!",WINDOW_WIDTH/2+70,33);}
        if(activePowerup!=null&&powerupTimer>0){g.setColor(new Color(100,220,255));g.setFont(new Font("Arial",Font.BOLD,12));g.drawString(activePowerup.toUpperCase()+" "+(int)powerupTimer+"s",WINDOW_WIDTH/2+70,48);}
        // Invincibility flash indicator
        if (invincibilityTimer > 0) {
            g.setFont(new Font("Arial", Font.BOLD, 13));
            g.setColor(new Color(255, 220, 50, 220));
            g.drawString("⚡ SAFE " + String.format("%.1f", invincibilityTimer) + "s", 14, 48);
        }
    }

    private void drawFood(Graphics2D g, FoodItem f) {
        int x=(int)(f.getX()-f.getWidth()/2), y=(int)(f.getY()-f.getHeight()/2);
        int w=(int)f.getWidth(), h=(int)f.getHeight();
        // Glow ring
        Color glow=f.isGoodFood()?new Color(80,220,100,55):new Color(220,70,70,55);
        g.setColor(glow); g.fillOval(x-5,y-5,w+10,h+10);
        Image img=FoodImageLoader.getFoodImage(f.getFoodName());
        if(img!=null){
            g.drawImage(img,x,y,w,h,null);
        } else {
            Color fill=f.isGoodFood()?new Color(60,190,80):new Color(220,70,70);
            g.setColor(fill);g.fillOval(x,y,w,h);g.setColor(fill.darker());
            g.setStroke(new BasicStroke(2));g.drawOval(x,y,w,h);
            g.setColor(Color.WHITE);g.setFont(new Font("Arial",Font.BOLD,10));
            String nm=f.getFoodName();g.drawString(nm,x+(w-g.getFontMetrics().stringWidth(nm))/2,y+h/2+4);
        }
        // Coloured border ring
        Color border=f.isGoodFood()?new Color(60,200,80,150):new Color(220,60,60,150);
        g.setColor(border);g.setStroke(new BasicStroke(2.5f));g.drawOval(x-1,y-1,w+2,h+2);
    }

    private void drawWall(Graphics2D g, engine.entities.Entity e) {
        if (!e.isActive()) return;
        int x=(int)(e.getX()-e.getWidth()/2), y=(int)(e.getY()-e.getHeight()/2);
        int w=(int)e.getWidth(), h=(int)e.getHeight();
        // Subtle stone/block look — adapts to current level theme
        Color wallFill, wallBorder;
        if (currentLevelScene != null) {
            int lvl = currentLevelScene.getConfig().levelNumber;
            if (lvl == 1) { wallFill = new Color(160,120,70,200); wallBorder = new Color(130,95,50,255); }
            else if (lvl == 2) { wallFill = new Color(80,110,60,200); wallBorder = new Color(55,85,35,255); }
            else               { wallFill = new Color(30,60,30,210); wallBorder = new Color(20,45,20,255); }
        } else { wallFill = new Color(100,100,100,200); wallBorder = new Color(70,70,70,255); }
        // Shadow
        g.setColor(new Color(0,0,0,60));
        g.fillRoundRect(x+3,y+3,w,h,6,6);
        // Body
        g.setColor(wallFill); g.fillRoundRect(x,y,w,h,6,6);
        // Top highlight
        g.setColor(new Color(255,255,255,35)); g.fillRoundRect(x,y,w,h/3,6,6);
        // Border
        g.setColor(wallBorder); g.setStroke(new BasicStroke(2));
        g.drawRoundRect(x,y,w,h,6,6);
    }

    private void drawPlayer(Graphics2D g) {
        int x=(int)(player.getX()-player.getWidth()/2), y=(int)(player.getY()-player.getHeight()/2);
        int w=(int)player.getWidth(), h=(int)player.getHeight();

        // During invincibility: flash every 150 ms — skip every other frame
        if (invincibilityTimer > 0 && ((int)(invincibilityTimer * 6.66f) % 2 == 0)) return;

        g.setColor(new Color(0,0,0,70)); g.fillOval(x+3,y+h-4,w,10);
        GradientPaint pg=new GradientPaint(x,y,new Color(110,170,255),x+w,y+h,new Color(50,100,200));
        g.setPaint(pg); g.fillOval(x,y,w,h);
        g.setColor(new Color(30,70,160)); g.setStroke(new BasicStroke(2.5f)); g.drawOval(x,y,w,h);
        g.setColor(new Color(255,255,255,85)); g.fillOval(x+6,y+5,w/3,h/4);

        // Invincibility ring — glowing halo
        if (invincibilityTimer > 0) {
            float alpha = Math.min(1f, invincibilityTimer / INVINCIBILITY_DURATION);
            g.setColor(new Color(255,220,50,(int)(160*alpha)));
            g.setStroke(new BasicStroke(3f));
            g.drawOval(x-6, y-6, w+12, h+12);
        }
    }

    private void drawBanner(Graphics2D g) {
        float alpha=Math.min(1f,levelUpBannerTimer/0.5f); int a=(int)(alpha*230);
        g.setColor(new Color(15,15,15,a)); g.fillRoundRect(100,218,600,92,20,20);
        g.setColor(new Color(255,220,50,a)); g.setStroke(new BasicStroke(3));
        g.drawRoundRect(100,218,600,92,20,20);
        g.setFont(new Font("Arial",Font.BOLD,32)); g.setColor(new Color(255,220,50,a));
        drawC(g,"⬆  "+levelUpBannerText,266);
        g.setFont(new Font("Arial",Font.PLAIN,16)); g.setColor(new Color(200,200,200,a));
        drawC(g,"Things are about to get faster — stay sharp!",294);
    }

    private void drawGameOverOverlay(Graphics2D g) {
        g.setColor(new Color(0,0,0,185)); g.fillRect(0,0,WINDOW_WIDTH,WINDOW_HEIGHT);
        g.setFont(new Font("Arial",Font.BOLD,50)); g.setColor(new Color(220,50,50));
        drawC(g,"GAME OVER",268);
        g.setFont(new Font("Arial",Font.PLAIN,21)); g.setColor(Color.WHITE);
        drawC(g,"Your health ran out — heading to results...",314);
    }

    private void drawLevelComplete(Graphics2D g) {
        // Animated starfield background
        GradientPaint gp = new GradientPaint(0,0,new Color(10,30,60),0,WINDOW_HEIGHT,new Color(20,10,50));
        g.setPaint(gp); g.fillRect(0,0,WINDOW_WIDTH,WINDOW_HEIGHT);

        // Twinkling stars
        Random rStars = new Random(77);
        g.setColor(new Color(255,255,255,120));
        for(int i=0;i<60;i++){
            int sx=rStars.nextInt(WINDOW_WIDTH), sy=rStars.nextInt(WINDOW_HEIGHT);
            int ss=1+rStars.nextInt(3);
            g.fillOval(sx,sy,ss,ss);
        }

        // Gold shimmer lines
        g.setColor(new Color(255,220,50,30));
        for(int i=0;i<WINDOW_WIDTH;i+=18) g.drawLine(i,0,i,WINDOW_HEIGHT);

        // Card
        int bx=110,by=130,bw=580,bh=310;
        g.setColor(new Color(20,40,80,220)); g.fillRoundRect(bx,by,bw,bh,24,24);
        g.setColor(new Color(255,220,50)); g.setStroke(new BasicStroke(3));
        g.drawRoundRect(bx,by,bw,bh,24,24);

        // Trophy icon area
        g.setFont(new Font("Arial",Font.BOLD,54)); g.setColor(new Color(255,220,50));
        drawC(g,"★  LEVEL COMPLETE!  ★", by+68);

        // Level info
        g.setFont(new Font("Arial",Font.BOLD,22)); g.setColor(Color.WHITE);
        drawC(g,"Level "+levelCompleteScene.getLevelNumber()+" cleared — great work!", by+118);

        // Score
        g.setFont(new Font("Arial",Font.BOLD,28)); g.setColor(new Color(100,240,120));
        drawC(g,"Score so far:  "+levelCompleteScene.getScore(), by+166);

        // HP bar snapshot
        int hbX=(WINDOW_WIDTH-240)/2, hbY=by+188, hbW=240, hbH=22;
        g.setColor(new Color(40,40,40)); g.fillRoundRect(hbX,hbY,hbW,hbH,8,8);
        Color hc = playerHealth>60?new Color(60,210,90):playerHealth>30?new Color(240,180,30):new Color(220,50,50);
        g.setColor(hc); g.fillRoundRect(hbX,hbY,(int)(hbW*playerHealth/(float)MAX_HEALTH),hbH,8,8);
        g.setColor(new Color(255,255,255,100)); g.setStroke(new BasicStroke(1.5f));
        g.drawRoundRect(hbX,hbY,hbW,hbH,8,8);
        g.setFont(new Font("Arial",Font.BOLD,13)); g.setColor(Color.WHITE);
        String hpStr="HP: "+playerHealth+"/"+MAX_HEALTH;
        g.drawString(hpStr, hbX+(hbW-g.getFontMetrics().stringWidth(hpStr))/2, hbY+15);

        // Next level
        g.setFont(new Font("Arial",Font.BOLD,20)); g.setColor(new Color(180,210,255));
        drawC(g,"Up next:  "+levelCompleteScene.getNextLevelName(), by+240);

        // Countdown + skip hint
        float t = levelCompleteScene.getTimeRemaining();
        g.setFont(new Font("Arial",Font.PLAIN,15)); g.setColor(new Color(160,160,160));
        drawC(g,"Starting in "+String.format("%.1f",t)+"s   (ENTER to skip)", by+280);

        // Countdown arc
        int arcR=22, arcX=WINDOW_WIDTH/2-arcR, arcY=by+253;
        g.setColor(new Color(60,60,80)); g.fillOval(arcX,arcY,arcR*2,arcR*2);
        g.setColor(new Color(100,200,255)); g.setStroke(new BasicStroke(4));
        int arcAngle=(int)(360*(t/3.5f));
        g.drawArc(arcX,arcY,arcR*2,arcR*2,90,-arcAngle);
    }

    private void drawEndScreen(Graphics2D g) {
        GradientPaint gp=new GradientPaint(0,0,new Color(12,12,28),0,WINDOW_HEIGHT,new Color(28,12,38));
        g.setPaint(gp); g.fillRect(0,0,WINDOW_WIDTH,WINDOW_HEIGHT);
        int bx=140,by=118,bw=520,bh=364;
        g.setColor(new Color(25,25,48,230)); g.fillRoundRect(bx,by,bw,bh,24,24);
        g.setColor(new Color(255,220,50)); g.setStroke(new BasicStroke(3)); g.drawRoundRect(bx,by,bw,bh,24,24);
        g.setFont(new Font("Arial",Font.BOLD,42)); g.setColor(new Color(255,220,50)); drawC(g,"GAME OVER",by+60);
        float[] res=endScene.getResults();
        g.setFont(new Font("Arial",Font.BOLD,24)); g.setColor(Color.WHITE);
        drawC(g,"Final Score:   "+(int)res[0],by+118);
        drawC(g,"Time Played:  "+String.format("%.1f",res[1])+"s",by+156);
        drawC(g,"HP Remaining: "+playerHealth+" / "+MAX_HEALTH,by+194);
        if((int)res[0]>=highScore&&(int)res[0]>0){g.setColor(new Color(255,220,50));g.setFont(new Font("Arial",Font.BOLD,20));drawC(g,"★  NEW HIGH SCORE!  ★",by+236);}
        g.setColor(new Color(170,170,170)); g.setFont(new Font("Arial",Font.PLAIN,18));
        drawC(g,"ENTER / SPACE — Play Again",by+288); drawC(g,"ESC — Main Menu",by+320);
    }

    private void drawPaused(Graphics2D g) {
        g.setColor(new Color(0,0,0,175)); g.fillRect(0,0,WINDOW_WIDTH,WINDOW_HEIGHT);
        int bx=220,by=200,bw=360,bh=180;
        g.setColor(new Color(18,18,52)); g.fillRoundRect(bx,by,bw,bh,20,20);
        g.setColor(Color.YELLOW); g.setStroke(new BasicStroke(3)); g.drawRoundRect(bx,by,bw,bh,20,20);
        g.setFont(new Font("Arial",Font.BOLD|Font.ITALIC,46)); g.setColor(Color.YELLOW); drawC(g,"PAUSED",by+65);
        g.setFont(new Font("Arial",Font.PLAIN,19)); g.setColor(Color.WHITE);
        drawC(g,"ESC — Resume",by+115); drawC(g,"Q — Quit to Menu",by+145);
    }

    private void drawC(Graphics2D g,String t,int y){g.drawString(t,(WINDOW_WIDTH-g.getFontMetrics().stringWidth(t))/2,y);}

    // ════════════════════════════════════════════════════════════════
    // INPUT
    // ════════════════════════════════════════════════════════════════
    @Override public void keyPressed(KeyEvent e) {
        int k=e.getKeyCode(); long t=System.currentTimeMillis();
        inputManager.getKeyboard().keyDown(k);
        if(k==KeyEvent.VK_UP)   inputManager.getKeyboard().keyDown(87);
        if(k==KeyEvent.VK_DOWN) inputManager.getKeyboard().keyDown(83);
        if(k==KeyEvent.VK_LEFT) inputManager.getKeyboard().keyDown(65);
        if(k==KeyEvent.VK_RIGHT)inputManager.getKeyboard().keyDown(68);
        engine.scene.Scene cur=sceneManager.getActiveScene(); if(cur==null)return;
        String n=cur.getSceneName();
        if("MenuScene".equals(n)){
            if(t-lastKeyPress<KEY_COOLDOWN)return;
            if(inputManager.isPressed(InputAction.MOVE_UP)){menuSelection=(menuSelection-1+menuOptions.length)%menuOptions.length;inputManager.getSpeaker().beep();lastKeyPress=t;}
            else if(inputManager.isPressed(InputAction.MOVE_DOWN)){menuSelection=(menuSelection+1)%menuOptions.length;inputManager.getSpeaker().beep();lastKeyPress=t;}
            else if(inputManager.isPressed(InputAction.CONFIRM)){handleMenu();lastKeyPress=t;}
        } else if("LevelComplete".equals(n)){
            if(k==KeyEvent.VK_ENTER||k==KeyEvent.VK_SPACE) levelCompleteScene.requestAdvance();
        } else if("Level1".equals(n)||"Level2".equals(n)||"Level3".equals(n)){
            if(inputManager.isPressed(InputAction.PAUSE)){
                if(paused){paused=false;timeManager.resume();lastTime=System.nanoTime();}
                else{paused=true;timeManager.pause();inputManager.getKeyboard().clearAll();}
            } else if(inputManager.isPressed(InputAction.ACTION_2)&&paused){
                paused=false;timeManager.resume();inputManager.getKeyboard().clearAll();sceneManager.loadScene("MenuScene");
            } else if(k==KeyEvent.VK_P&&!paused&&powerupAvailable) showPowerupDialog();
        } else if("EndScene".equals(n)){
            if(k==KeyEvent.VK_SPACE||inputManager.isPressed(InputAction.CONFIRM)){inputManager.getKeyboard().clearAll();endScene.resetRequests();startNewGame();lastTime=System.nanoTime();}
            else if(inputManager.isPressed(InputAction.CANCEL)){inputManager.getKeyboard().clearAll();endScene.resetRequests();sceneManager.loadScene("MenuScene");}
        }
    }
    @Override public void keyReleased(KeyEvent e){
        int k=e.getKeyCode(); inputManager.getKeyboard().keyUp(k);
        if(k==KeyEvent.VK_UP)   inputManager.getKeyboard().keyUp(87);
        if(k==KeyEvent.VK_DOWN) inputManager.getKeyboard().keyUp(83);
        if(k==KeyEvent.VK_LEFT) inputManager.getKeyboard().keyUp(65);
        if(k==KeyEvent.VK_RIGHT)inputManager.getKeyboard().keyUp(68);
    }
    @Override public void keyTyped(KeyEvent e){}

    private void handleMenu(){
        switch(menuSelection){
            case 0: startNewGame(); lastTime=System.nanoTime(); break;
            case 1:
                JOptionPane.showMessageDialog(this,
                    "CONTROLS\n  WASD / Arrow Keys — Move\n  ESC — Pause   Q — Quit\n  P — Powerup\n\n"
                  + "GAMEPLAY\n  Green food = +1 pt (respawns immediately)\n"
                  + "  Red food = -20 HP then a quiz pops up\n"
                  + "  Correct = +1 pt +5 HP   Wrong = -10 HP\n"
                  + "  HP reaches 0 = Game Over\n\n"
                  + "LEVELS\n  Level 1 Kitchen  — reach 10 pts\n"
                  + "  Level 2 Market   — reach 25 pts\n"
                  + "  Level 3 Jungle   — survive as long as possible\n\n"
                  + "POWERUPS (unlocked at 8, 20, 40 pts)\n"
                  + "  Freeze  — stops all bad food 5 s\n"
                  + "  Shield  — absorbs 1 bad food hit\n"
                  + "  Double  — 2× points for 10 s",
                    "How to Play",JOptionPane.INFORMATION_MESSAGE);
                break;
            case 2: System.exit(0);
        }
    }

    public static void main(String[] args){
        SwingUtilities.invokeLater(()->{
            JFrame frame=new JFrame(TITLE);
            PlayableGame game=new PlayableGame();
            frame.setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
            frame.setResizable(false); frame.add(game); frame.pack();
            frame.setLocationRelativeTo(null); frame.setVisible(true);
            game.requestFocusInWindow(); game.start();
        });
    }
}
