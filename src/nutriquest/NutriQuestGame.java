package nutriquest;

import engine.core.GameMaster;
import engine.entities.Player;
import engine.entities.Entity;
import engine.managers.*;
import engine.input.InputAction;
import engine.scene.Scene;
import engine.scene.MenuScene;
import engine.scene.EndScene;

import javax.swing.*;
import java.awt.*;
import java.awt.event.*;

/**
 * NutriQuest - Healthy Eating Adventure
 * Kids 8-12 learn nutrition. Collect green foods, avoid red. Quizzes grant bonus time.
 */
public class NutriQuestGame extends JPanel implements KeyListener, Runnable {
    private static final int WINDOW_WIDTH = 800;
    private static final int WINDOW_HEIGHT = 600;
    private static final String TITLE = "NutriQuest - Healthy Eating Adventure";

    private GameMaster gameMaster;
    private SceneManager sceneManager;
    private EntityManager entityManager;
    private InputOutputManager inputManager;
    private MovementManager movementManager;
    private CollisionManager collisionManager;
    private TimeManager timeManager;
    private Player player;

    private MenuScene menuScene;
    private NutriQuestScene currentMealScene;
    private BreakfastScene breakfastScene;
    private LunchScene lunchScene;
    private DinnerScene dinnerScene;
    private EndScene endScene;

    private int currentLevel;
    private boolean running;
    private Thread gameThread;
    private long lastTime;
    private int fps;
    private int frameCount;
    private long fpsTimer;

    public NutriQuestGame() {
        setPreferredSize(new Dimension(WINDOW_WIDTH, WINDOW_HEIGHT));
        setBackground(Color.BLACK);
        setFocusable(true);
        addKeyListener(this);

        gameMaster = new GameMaster();
        gameMaster.initialize();
        sceneManager = gameMaster.getSceneManager();
        entityManager = gameMaster.getEntityManager();
        inputManager = gameMaster.getInputOutputManager();
        movementManager = gameMaster.getMovementManager();
        collisionManager = gameMaster.getCollisionManager();
        timeManager = gameMaster.getTimeManager();

        inputManager.bindAction(InputAction.CONFIRM, KeyEvent.VK_ENTER);
        inputManager.bindAction(InputAction.CANCEL, KeyEvent.VK_ESCAPE);
        inputManager.bindAction(InputAction.ACTION_1, KeyEvent.VK_1);
        inputManager.bindAction(InputAction.ACTION_2, KeyEvent.VK_2);

        registerScenes();
        sceneManager.loadScene("MenuScene");
    }

    private void registerScenes() {
        menuScene = new MenuScene(inputManager);
        breakfastScene = new BreakfastScene(entityManager, movementManager, collisionManager, inputManager);
        lunchScene = new LunchScene(entityManager, movementManager, collisionManager, inputManager);
        dinnerScene = new DinnerScene(entityManager, movementManager, collisionManager, inputManager);
        endScene = new EndScene(inputManager);

        breakfastScene.setWorldSize(WINDOW_WIDTH, WINDOW_HEIGHT);
        lunchScene.setWorldSize(WINDOW_WIDTH, WINDOW_HEIGHT);
        dinnerScene.setWorldSize(WINDOW_WIDTH, WINDOW_HEIGHT);

        sceneManager.addScene("MenuScene", menuScene);
        sceneManager.addScene("Breakfast", breakfastScene);
        sceneManager.addScene("Lunch", lunchScene);
        sceneManager.addScene("Dinner", dinnerScene);
        sceneManager.addScene("EndScene", endScene);
    }

    private void startGame() {
        currentLevel = 0;
        startNextLevel();
    }

    private void startNextLevel() {
        entityManager.clear();
        collisionManager.clearAll();

        float px = WINDOW_WIDTH / 2f;
        float py = WINDOW_HEIGHT / 2f;
        player = new Player(px, py, "Player");
        player.setSpeed(200.0f);
        player.setWidth(32);
        player.setHeight(32);
        entityManager.addEntity(player);
        movementManager.addEntity(player);
        collisionManager.addCollidable(player, "player");

        collisionManager.setLayerCollision("player", "green_food", true);
        collisionManager.setLayerCollision("player", "red_food", true);

        if (currentLevel == 0) {
            currentMealScene = breakfastScene;
            sceneManager.loadScene("Breakfast");
        } else if (currentLevel == 1) {
            currentMealScene = lunchScene;
            sceneManager.loadScene("Lunch");
        } else {
            currentMealScene = dinnerScene;
            sceneManager.loadScene("Dinner");
        }

        currentMealScene.setPlayer(player);
    }

    public void start() {
        if (running) return;
        running = true;
        gameThread = new Thread(this);
        gameThread.start();
    }

    @Override
    public void run() {
        lastTime = System.nanoTime();
        fpsTimer = System.currentTimeMillis();

        while (running) {
            long now = System.nanoTime();
            float rawDt = (now - lastTime) / 1_000_000_000.0f;
            lastTime = now;

            timeManager.update(rawDt);
            float dt = timeManager.getDeltaTime();

            inputManager.pollInput();
            sceneManager.update(dt);
            inputManager.processOutput();

            applySlowEffect(dt);
            checkSceneTransitions();

            repaint();

            frameCount++;
            if (System.currentTimeMillis() - fpsTimer >= 1000) {
                fps = frameCount;
                frameCount = 0;
                fpsTimer = System.currentTimeMillis();
            }

            try { Thread.sleep(16); } catch (InterruptedException e) { }
        }
    }

    private void applySlowEffect(float dt) {
        if (player == null) return;
        if (currentMealScene != null && currentMealScene.getNutritionTracker().isSlowed()) {
            player.setSpeed(80.0f);
        } else {
            player.setSpeed(200.0f);
        }
    }

    private void checkSceneTransitions() {
        Scene current = sceneManager.getActiveScene();
        if (current == null) return;

        if ("MenuScene".equals(current.getSceneName())) {
            engine.scene.MenuScene menu = (engine.scene.MenuScene) current;
            if (menu.isTransitionRequested()) {
                menu.resetTransition();
                startGame();
            }
        } else if (currentMealScene != null && currentMealScene.isGameOver()) {
            endScene.setResults(currentMealScene.getScore(),
                    currentMealScene.getLevelDuration() - currentMealScene.getTimeRemaining());
            sceneManager.loadScene("EndScene");
        } else if (currentMealScene != null && !currentMealScene.isGameOver()
                && currentMealScene.getTimeRemaining() <= 0) {
            currentLevel++;
            if (currentLevel < 3) {
                startNextLevel();
            } else {
                endScene.setResults(currentMealScene.getScore(), currentMealScene.getLevelDuration());
                sceneManager.loadScene("EndScene");
            }
        } else if ("EndScene".equals(current.getSceneName())) {
            if (endScene.isRestartRequested()) {
                endScene.resetRequests();
                currentLevel = 0;
                startGame();
            } else if (endScene.isExitRequested()) {
                endScene.resetRequests();
                sceneManager.loadScene("MenuScene");
            }
        }
    }

    @Override
    protected void paintComponent(Graphics g) {
        super.paintComponent(g);
        Graphics2D g2d = (Graphics2D) g;
        g2d.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);

        Scene current = sceneManager.getActiveScene();
        if (current == null) {
            drawMenu(g2d);
            return;
        }

        switch (current.getSceneName()) {
            case "MenuScene":
                drawNutriQuestMenu(g2d);
                break;
            case "Breakfast":
            case "Lunch":
            case "Dinner":
                drawGame(g2d);
                if (currentMealScene != null && currentMealScene.getQuizManager().isQuizActive()) {
                    drawQuiz(g2d);
                }
                if (currentMealScene != null && currentMealScene.isGameOver()) {
                    drawGameOver(g2d);
                }
                break;
            case "EndScene":
                drawGame(g2d);
                drawGameOverScreen(g2d);
                break;
            default:
                drawNutriQuestMenu(g2d);
        }
    }

    private void drawNutriQuestMenu(Graphics2D g2d) {
        GradientPaint gp = new GradientPaint(0, 0, new Color(30, 80, 40), 0, WINDOW_HEIGHT, new Color(20, 50, 30));
        g2d.setPaint(gp);
        g2d.fillRect(0, 0, WINDOW_WIDTH, WINDOW_HEIGHT);

        g2d.setColor(new Color(180, 255, 180));
        g2d.setFont(new Font("Arial", Font.BOLD, 42));
        drawCentered(g2d, "NUTRIQUEST", 120);
        g2d.setFont(new Font("Arial", Font.PLAIN, 20));
        drawCentered(g2d, "Healthy Eating Adventure", 165);

        g2d.setColor(Color.WHITE);
        g2d.setFont(new Font("Arial", Font.BOLD, 24));
        drawCentered(g2d, "Collect green foods (fruits & veggies) for points!", 230);
        drawCentered(g2d, "Avoid red junk food - it slows you and reduces health.", 265);
        drawCentered(g2d, "Answer nutrition quizzes for bonus time!", 300);

        g2d.setColor(new Color(100, 255, 100));
        g2d.setFont(new Font("Arial", Font.BOLD, 28));
        drawCentered(g2d, "Press ENTER to Start", 400);

        g2d.setColor(Color.LIGHT_GRAY);
        g2d.setFont(new Font("Arial", Font.PLAIN, 16));
        drawCentered(g2d, "WASD or Arrows to move", 480);
    }

    private void drawMenu(Graphics2D g2d) {
        drawNutriQuestMenu(g2d);
    }

    private void drawGame(Graphics2D g2d) {
        g2d.setColor(new Color(240, 248, 230));
        g2d.fillRect(0, 0, WINDOW_WIDTH, WINDOW_HEIGHT);

        g2d.setColor(new Color(200, 220, 180));
        for (int x = 0; x < WINDOW_WIDTH; x += 40)
            g2d.drawLine(x, 0, x, WINDOW_HEIGHT);
        for (int y = 0; y < WINDOW_HEIGHT; y += 40)
            g2d.drawLine(0, y, WINDOW_WIDTH, y);

        for (Entity e : entityManager.getActiveEntities()) {
            if (e instanceof Food) {
                Food f = (Food) e;
                Color c = f.isHealthy() ? new Color(50, 180, 80) : new Color(220, 60, 60);
                drawEntity(g2d, e, c);
            }
        }

        if (player != null && player.isActive()) {
            drawEntity(g2d, player, new Color(60, 120, 200));
        }

        g2d.setColor(new Color(0, 0, 0, 200));
        g2d.fillRect(0, 0, WINDOW_WIDTH, 55);
        g2d.setColor(Color.WHITE);
        g2d.setFont(new Font("Arial", Font.BOLD, 18));
        g2d.drawString("Score: " + (currentMealScene != null ? currentMealScene.getScore() : 0), 20, 32);
        g2d.drawString("Time: " + String.format("%.1f", currentMealScene != null ? currentMealScene.getTimeRemaining() : 0) + "s", 180, 32);
        if (currentMealScene != null) {
            float h = currentMealScene.getNutritionTracker().getHealth();
            g2d.drawString("Health: " + (int) h, 340, 32);
            g2d.setColor(h > 50 ? Color.GREEN : (h > 25 ? Color.ORANGE : Color.RED));
            g2d.fillRect(450, 15, (int) (200 * h / 100), 25);
            g2d.setColor(Color.WHITE);
            g2d.drawRect(450, 15, 200, 25);
        }
        g2d.drawString("FPS: " + fps, 680, 32);
    }

    private void drawQuiz(Graphics2D g2d) {
        QuizManager qm = currentMealScene.getQuizManager();
        g2d.setColor(new Color(0, 0, 0, 220));
        g2d.fillRect(0, 0, WINDOW_WIDTH, WINDOW_HEIGHT);

        int bx = 80, by = 150, bw = 640, bh = 300;
        g2d.setColor(new Color(40, 100, 50));
        g2d.fillRoundRect(bx, by, bw, bh, 20, 20);
        g2d.setColor(new Color(100, 200, 120));
        g2d.setStroke(new BasicStroke(4));
        g2d.drawRoundRect(bx, by, bw, bh, 20, 20);

        g2d.setColor(Color.WHITE);
        g2d.setFont(new Font("Arial", Font.BOLD, 22));
        drawCentered(g2d, "NUTRITION QUIZ - Correct answer = +10 seconds!", by + 45);
        g2d.setFont(new Font("Arial", Font.PLAIN, 20));
        drawCentered(g2d, qm.getCurrentQuestion(), by + 95);

        String[] choices = qm.getChoices();
        g2d.setFont(new Font("Arial", Font.PLAIN, 18));
        for (int i = 0; i < choices.length; i++) {
            drawCentered(g2d, (i + 1) + ". " + choices[i], by + 140 + i * 35);
        }
        g2d.drawString("Press 1-4 to answer", bx + 20, by + bh - 30);
    }

    private void drawGameOver(Graphics2D g2d) {
        g2d.setColor(new Color(0, 0, 0, 180));
        g2d.fillRect(0, 0, WINDOW_WIDTH, WINDOW_HEIGHT);
        g2d.setColor(Color.RED);
        g2d.setFont(new Font("Arial", Font.BOLD, 36));
        drawCentered(g2d, "GAME OVER", 250);
        g2d.setColor(Color.WHITE);
        g2d.setFont(new Font("Arial", Font.PLAIN, 20));
        drawCentered(g2d, "Final Score: " + (currentMealScene != null ? currentMealScene.getScore() : 0), 310);
    }

    private void drawGameOverScreen(Graphics2D g2d) {
        g2d.setColor(new Color(0, 0, 0, 200));
        g2d.fillRect(0, 0, WINDOW_WIDTH, WINDOW_HEIGHT);
        int bx = 150, by = 150, bw = 500, bh = 300;
        g2d.setColor(new Color(40, 80, 40));
        g2d.fillRoundRect(bx, by, bw, bh, 20, 20);
        g2d.setColor(new Color(80, 180, 100));
        g2d.setStroke(new BasicStroke(4));
        g2d.drawRoundRect(bx, by, bw, bh, 20, 20);
        g2d.setColor(Color.WHITE);
        g2d.setFont(new Font("Arial", Font.BOLD, 32));
        drawCentered(g2d, "LEVEL COMPLETE!", by + 70);
        g2d.setFont(new Font("Arial", Font.PLAIN, 22));
        float[] res = endScene.getResults();
        drawCentered(g2d, "Final Score: " + (int) res[0], by + 130);
        drawCentered(g2d, "ENTER to Play Again", by + 200);
        drawCentered(g2d, "ESC for Menu", by + 240);
    }

    private void drawEntity(Graphics2D g2d, Entity e, Color c) {
        if (!e.isActive()) return;
        int x = (int) (e.getX() - e.getWidth() / 2);
        int y = (int) (e.getY() - e.getHeight() / 2);
        int w = (int) e.getWidth(), h = (int) e.getHeight();
        g2d.setColor(c);
        g2d.fillOval(x, y, w, h);
        g2d.setColor(c.darker());
        g2d.setStroke(new BasicStroke(2));
        g2d.drawOval(x, y, w, h);
    }

    private void drawCentered(Graphics2D g2d, String text, int y) {
        int w = g2d.getFontMetrics().stringWidth(text);
        g2d.drawString(text, (WINDOW_WIDTH - w) / 2, y);
    }

    @Override
    public void keyPressed(KeyEvent e) {
        int k = e.getKeyCode();
        inputManager.getKeyboard().keyDown(k);
        if (k == KeyEvent.VK_UP) inputManager.getKeyboard().keyDown(87);
        else if (k == KeyEvent.VK_DOWN) inputManager.getKeyboard().keyDown(83);
        else if (k == KeyEvent.VK_LEFT) inputManager.getKeyboard().keyDown(65);
        else if (k == KeyEvent.VK_RIGHT) inputManager.getKeyboard().keyDown(68);

        Scene current = sceneManager.getActiveScene();
        if (current == null) return;

        if ("MenuScene".equals(current.getSceneName()) && (k == KeyEvent.VK_ENTER || k == KeyEvent.VK_SPACE)) {
            startGame();
        } else if ("MenuScene".equals(current.getSceneName())) {
            engine.scene.MenuScene menu = (engine.scene.MenuScene) current;
            if (menu.isTransitionRequested()) {
                menu.resetTransition();
                startGame();
            }
        } else if (currentMealScene != null && currentMealScene.getQuizManager().isQuizActive()) {
            String[] choices = currentMealScene.getQuizManager().getChoices();
            int idx = -1;
            if (k == KeyEvent.VK_1) idx = 0;
            else if (k == KeyEvent.VK_2) idx = 1;
            else if (k == KeyEvent.VK_3) idx = 2;
            else if (k == KeyEvent.VK_4) idx = 3;
            if (idx >= 0 && idx < choices.length) {
                float bonus = currentMealScene.getQuizManager().submitAnswer(choices[idx]);
                if (bonus > 0) currentMealScene.addTime(bonus);
            }
        } else if ("EndScene".equals(current.getSceneName())) {
            if (k == KeyEvent.VK_ENTER || k == KeyEvent.VK_SPACE) {
                endScene.resetRequests();
                entityManager.clear();
                currentLevel = 0;
                startGame();
            } else if (k == KeyEvent.VK_ESCAPE) {
                endScene.resetRequests();
                sceneManager.loadScene("MenuScene");
            }
        }
    }

    @Override
    public void keyReleased(KeyEvent e) {
        int k = e.getKeyCode();
        inputManager.getKeyboard().keyUp(k);
        if (k == KeyEvent.VK_UP) inputManager.getKeyboard().keyUp(87);
        else if (k == KeyEvent.VK_DOWN) inputManager.getKeyboard().keyUp(83);
        else if (k == KeyEvent.VK_LEFT) inputManager.getKeyboard().keyUp(65);
        else if (k == KeyEvent.VK_RIGHT) inputManager.getKeyboard().keyUp(68);
    }

    @Override
    public void keyTyped(KeyEvent e) { }

    public static void main(String[] args) {
        SwingUtilities.invokeLater(() -> {
            JFrame frame = new JFrame(TITLE);
            NutriQuestGame game = new NutriQuestGame();
            frame.setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
            frame.setResizable(false);
            frame.add(game);
            frame.pack();
            frame.setLocationRelativeTo(null);
            frame.setVisible(true);
            game.requestFocusInWindow();
            game.start();
        });
    }
}
