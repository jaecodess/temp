package simulation;

import javax.swing.*;
import javax.swing.border.*;
import java.awt.*;
import java.awt.event.*;
import java.awt.geom.*;
import java.util.Timer;
import java.util.TimerTask;

/**
 * Quiz dialog — visually polished, fully keyboard-navigable.
 *
 * Controls:
 *   ↑ / ↓ / ← / →   navigate between answer choices
 *   ENTER            confirm selected answer
 *   Mouse click      also works
 *
 * Timer is displayed with a countdown arc and a high-contrast pill badge.
 * Returns true if the selected answer is correct.
 */
public class QuizDialog extends JDialog {

    // ── Constants ────────────────────────────────────────────────────
    private static final int COUNTDOWN_SECONDS = 30;

    // Palette
    private static final Color BG_DARK       = new Color(18, 20, 38);
    private static final Color BG_CARD       = new Color(28, 30, 55);
    private static final Color ACCENT_BLUE   = new Color(80, 140, 255);
    private static final Color ACCENT_GREEN  = new Color(60, 210, 100);
    private static final Color ACCENT_ORANGE = new Color(255, 165, 40);
    private static final Color ACCENT_RED    = new Color(230, 60, 60);
    private static final Color TEXT_PRIMARY  = new Color(235, 235, 245);
    private static final Color TEXT_DIM      = new Color(155, 155, 175);
    private static final Color BTN_IDLE      = new Color(38, 40, 70);
    private static final Color BTN_HOVER     = new Color(55, 60, 100);
    private static final Color BTN_SELECTED  = new Color(65, 100, 200);

    // ── State ────────────────────────────────────────────────────────
    private int selectedAnswer = -1;
    private int hoveredIndex   = 0;          // keyboard highlight
    private final int correctIndex;
    private int countdown = COUNTDOWN_SECONDS;
    private Timer countdownTimer;
    private volatile boolean answered = false;

    // ── Widgets ──────────────────────────────────────────────────────
    private AnswerButton[] answerButtons;
    private TimerPanel     timerPanel;

    // ════════════════════════════════════════════════════════════════
    public QuizDialog(Frame parent, String question, String[] options, int correctIndex) {
        super(parent, "Nutrition Quiz", true);
        this.correctIndex = correctIndex;

        setUndecorated(true);         // we paint our own title bar
        setSize(580, 400);
        setLocationRelativeTo(parent);
        setDefaultCloseOperation(DO_NOTHING_ON_CLOSE);

        JPanel root = new JPanel(new BorderLayout(0, 0)) {
            @Override protected void paintComponent(Graphics g) {
                Graphics2D g2 = (Graphics2D) g;
                g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
                // Outer rounded card
                g2.setColor(new Color(10, 12, 25, 220));
                g2.fill(new RoundRectangle2D.Float(0, 0, getWidth(), getHeight(), 20, 20));
                g2.setColor(BG_CARD);
                g2.fill(new RoundRectangle2D.Float(2, 2, getWidth()-4, getHeight()-4, 18, 18));
                // Top accent stripe
                g2.setColor(ACCENT_BLUE);
                g2.fillRoundRect(0, 0, getWidth(), 5, 4, 4);
            }
        };
        root.setOpaque(false);
        root.setBorder(new EmptyBorder(0, 0, 0, 0));

        // ── Title row ─────────────────────────────────────────────
        JPanel titleRow = new JPanel(new BorderLayout());
        titleRow.setOpaque(false);
        titleRow.setBorder(new EmptyBorder(18, 24, 4, 24));

        JLabel icon = new JLabel("❓");
        icon.setFont(new Font("Segoe UI Emoji", Font.PLAIN, 22));
        icon.setForeground(ACCENT_BLUE);

        JLabel title = new JLabel("NUTRITION QUIZ");
        title.setFont(new Font("Arial", Font.BOLD, 15));
        title.setForeground(ACCENT_BLUE);
        title.setBorder(new EmptyBorder(0, 10, 0, 0));

        timerPanel = new TimerPanel();

        JPanel titleLeft = new JPanel(new FlowLayout(FlowLayout.LEFT, 0, 0));
        titleLeft.setOpaque(false);
        titleLeft.add(icon);
        titleLeft.add(title);

        titleRow.add(titleLeft, BorderLayout.WEST);
        titleRow.add(timerPanel, BorderLayout.EAST);

        // ── Question ──────────────────────────────────────────────
        JPanel qPanel = new JPanel(new BorderLayout());
        qPanel.setOpaque(false);
        qPanel.setBorder(new EmptyBorder(10, 28, 10, 28));

        JLabel qLabel = new JLabel("<html><div style='text-align:center'>" + question + "</div></html>");
        qLabel.setFont(new Font("Arial", Font.BOLD, 17));
        qLabel.setForeground(TEXT_PRIMARY);
        qLabel.setHorizontalAlignment(SwingConstants.CENTER);
        qPanel.add(qLabel, BorderLayout.CENTER);

        // Hint line
        JLabel hint = new JLabel("↑↓ change row   ←→ change column   ENTER to confirm");
        hint.setFont(new Font("Arial", Font.PLAIN, 11));
        hint.setForeground(TEXT_DIM);
        hint.setHorizontalAlignment(SwingConstants.CENTER);
        qPanel.add(hint, BorderLayout.SOUTH);

        // ── Answer buttons ────────────────────────────────────────
        JPanel answerPanel = new JPanel(new GridLayout(2, 2, 10, 10));
        answerPanel.setOpaque(false);
        answerPanel.setBorder(new EmptyBorder(4, 24, 24, 24));

        answerButtons = new AnswerButton[options.length];
        String[] prefixes = {"A", "B", "C", "D"};
        for (int i = 0; i < options.length; i++) {
            final int idx = i;
            answerButtons[i] = new AnswerButton(prefixes[i], options[i]);
            answerButtons[i].addActionListener(e -> selectAnswer(idx));
            answerButtons[i].addMouseListener(new MouseAdapter() {
                @Override public void mouseEntered(MouseEvent e) { setHovered(idx); }
            });
            answerPanel.add(answerButtons[i]);
        }

        // ── Assemble ──────────────────────────────────────────────
        JPanel top = new JPanel(new BorderLayout());
        top.setOpaque(false);
        top.add(titleRow, BorderLayout.NORTH);
        top.add(qPanel,   BorderLayout.CENTER);

        root.add(top,         BorderLayout.NORTH);
        root.add(answerPanel, BorderLayout.CENTER);
        setContentPane(root);

        // Initial highlight
        setHovered(0);

        // ── Keyboard navigation ────────────────────────────────────
        // Grid layout:   [A(0)] [B(1)]
        //                [C(2)] [D(3)]
        //
        // UP/DOWN   → same column, toggle row  (index XOR 2)
        // LEFT/RIGHT → same row,   toggle col  (index XOR 1)
        //
        // Small timestamp guard: ignore keys fired within 150 ms of opening
        // so a held game key doesn't immediately fire on quiz open.
        final long openTime = System.currentTimeMillis();
        final long KEY_OPEN_GUARD_MS = 150;

        KeyAdapter keyNav = new KeyAdapter() {
            @Override public void keyPressed(KeyEvent e) {
                if (System.currentTimeMillis() - openTime < KEY_OPEN_GUARD_MS) return;
                int k = e.getKeyCode();
                if      (k == KeyEvent.VK_UP    || k == KeyEvent.VK_W) { setHovered(hoveredIndex ^ 2); return; }
                else if (k == KeyEvent.VK_DOWN  || k == KeyEvent.VK_S) { setHovered(hoveredIndex ^ 2); return; }
                else if (k == KeyEvent.VK_LEFT  || k == KeyEvent.VK_A) { setHovered(hoveredIndex ^ 1); return; }
                else if (k == KeyEvent.VK_RIGHT || k == KeyEvent.VK_D) { setHovered(hoveredIndex ^ 1); return; }
                else if (k == KeyEvent.VK_ENTER || k == KeyEvent.VK_SPACE) { selectAnswer(hoveredIndex); }
            }
        };
        // Attach to dialog and all children so focus doesn't matter
        addKeyListener(keyNav);
        root.addKeyListener(keyNav);
        qPanel.addKeyListener(keyNav);
        for (AnswerButton ab : answerButtons) ab.addKeyListener(keyNav);

        setFocusable(true);
        requestFocus();

        // ── Countdown timer ───────────────────────────────────────
        countdownTimer = new Timer(true);
        countdownTimer.scheduleAtFixedRate(new TimerTask() {
            @Override public void run() {
                SwingUtilities.invokeLater(() -> {
                    if (answered) { countdownTimer.cancel(); return; }
                    countdown--;
                    timerPanel.setSeconds(countdown);
                    if (countdown <= 0) {
                        answered = true;
                        countdownTimer.cancel();
                        dispose();
                    }
                });
            }
        }, 1000, 1000);
    }

    // ── Helpers ───────────────────────────────────────────────────────
    private void setHovered(int idx) {
        hoveredIndex = idx;
        for (int i = 0; i < answerButtons.length; i++) {
            answerButtons[i].setHighlighted(i == idx);
        }
    }

    private void selectAnswer(int index) {
        if (answered) return;
        answered = true;
        selectedAnswer = index;
        countdownTimer.cancel();
        dispose();
    }

    // ════════════════════════════════════════════════════════════════
    // Static factory
    // ════════════════════════════════════════════════════════════════
    public static boolean showQuiz(Frame parent, String question,
                                   String[] options, int correctIndex) {
        QuizDialog dialog = new QuizDialog(parent, question, options, correctIndex);
        dialog.setVisible(true);
        return dialog.selectedAnswer == dialog.correctIndex;
    }

    // ════════════════════════════════════════════════════════════════
    // Inner: custom answer button
    // ════════════════════════════════════════════════════════════════
    private static class AnswerButton extends JButton {
        private final String prefix;
        private final String labelText;
        private boolean highlighted = false;

        AnswerButton(String prefix, String label) {
            super();
            this.prefix    = prefix;
            this.labelText = label;
            setOpaque(false);
            setContentAreaFilled(false);
            setBorderPainted(false);
            setFocusPainted(false);
            setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
            setPreferredSize(new Dimension(220, 58));
            setFont(new Font("Arial", Font.BOLD, 14));
        }

        void setHighlighted(boolean v) {
            highlighted = v;
            repaint();
        }

        @Override protected void paintComponent(Graphics g) {
            Graphics2D g2 = (Graphics2D) g;
            g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
            int w = getWidth(), h = getHeight();

            // Background
            Color bg = highlighted ? BTN_SELECTED
                     : getModel().isPressed() ? BTN_HOVER
                     : BTN_IDLE;
            g2.setColor(bg);
            g2.fillRoundRect(0, 0, w, h, 12, 12);

            // Border
            Color border = highlighted ? ACCENT_BLUE.brighter() : new Color(60, 65, 110);
            g2.setColor(border);
            g2.setStroke(new BasicStroke(highlighted ? 2f : 1f));
            g2.drawRoundRect(0, 0, w-1, h-1, 12, 12);

            // Letter badge
            int badgeSize = 26;
            int bx = 10, by = (h - badgeSize) / 2;
            g2.setColor(highlighted ? ACCENT_BLUE : new Color(55, 60, 100));
            g2.fillRoundRect(bx, by, badgeSize, badgeSize, 8, 8);
            g2.setFont(new Font("Arial", Font.BOLD, 13));
            g2.setColor(TEXT_PRIMARY);
            FontMetrics fm = g2.getFontMetrics();
            g2.drawString(prefix,
                bx + (badgeSize - fm.stringWidth(prefix)) / 2,
                by + (badgeSize + fm.getAscent() - fm.getDescent()) / 2);

            // Label
            g2.setFont(new Font("Arial", Font.PLAIN, 14));
            g2.setColor(highlighted ? Color.WHITE : TEXT_PRIMARY);
            fm = g2.getFontMetrics();
            String display = labelText;
            int maxW = w - badgeSize - 28;
            while (fm.stringWidth(display) > maxW && display.length() > 4)
                display = display.substring(0, display.length() - 4) + "…";
            g2.drawString(display,
                bx + badgeSize + 10,
                by + (badgeSize + fm.getAscent() - fm.getDescent()) / 2);
        }
    }

    // ════════════════════════════════════════════════════════════════
    // Inner: countdown arc panel
    // ════════════════════════════════════════════════════════════════
    private static class TimerPanel extends JPanel {
        private int seconds = COUNTDOWN_SECONDS;
        private static final int SIZE = 54;

        TimerPanel() {
            setPreferredSize(new Dimension(SIZE + 20, SIZE + 10));
            setOpaque(false);
        }

        void setSeconds(int s) {
            seconds = Math.max(0, s);
            repaint();
        }

        @Override protected void paintComponent(Graphics g) {
            Graphics2D g2 = (Graphics2D) g;
            g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);

            int cx = getWidth() / 2, cy = getHeight() / 2;
            int r  = SIZE / 2;
            int ox = cx - r, oy = cy - r;

            // Track circle
            g2.setColor(new Color(40, 42, 70));
            g2.setStroke(new BasicStroke(5, BasicStroke.CAP_ROUND, BasicStroke.JOIN_ROUND));
            g2.drawOval(ox, oy, SIZE, SIZE);

            // Progress arc
            float frac = seconds / (float) COUNTDOWN_SECONDS;
            Color arcColor = frac > 0.5f ? ACCENT_GREEN
                           : frac > 0.25f ? ACCENT_ORANGE
                           : ACCENT_RED;
            g2.setColor(arcColor);
            g2.setStroke(new BasicStroke(5, BasicStroke.CAP_ROUND, BasicStroke.JOIN_ROUND));
            int arcDeg = (int)(360 * frac);
            g2.drawArc(ox, oy, SIZE, SIZE, 90, arcDeg);

            // Dark pill badge behind the number
            int pillW = 34, pillH = 20;
            int px = cx - pillW/2, py = cy - pillH/2;
            g2.setColor(new Color(10, 10, 20, 200));
            g2.fillRoundRect(px, py, pillW, pillH, 8, 8);

            // Number — always white on the dark pill, very readable
            g2.setFont(new Font("Arial", Font.BOLD, 15));
            g2.setColor(arcColor);
            FontMetrics fm = g2.getFontMetrics();
            String txt = String.valueOf(seconds);
            g2.drawString(txt, cx - fm.stringWidth(txt)/2, cy + fm.getAscent()/2 - 1);
        }
    }
}
