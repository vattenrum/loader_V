using System.Drawing.Drawing2D;
using System.Drawing;
using System.ComponentModel;
using System;
using System.Windows.Forms;

/// <summary>
/// Flex Theme v0.2 BETA
/// Author: Layle
/// Special thanks to: Nervo [uid = 338713] for the base!
/// </summary>

class Drawing
{
    public static void DrawWithOutline(Graphics G, Brush InnerBrush, Brush OutlineBrush, Rectangle Rectangle)
    {
        G.FillRectangle(InnerBrush, Rectangle);
        G.DrawRectangle(new Pen(OutlineBrush), Rectangle);
    }

    public static Brush tehColor = new SolidBrush(Color.FromArgb(220, 20, 60));
    public static Brush underlineColor = new SolidBrush(Color.FromArgb(223, 238, 244));
}

class FlexForm : ContainerControl
{

    #region "MouseStates"
    bool IsDown = false;
    int TitleHeight = 32;
    Point MouseCurrent = new Point(0, 0);

    protected override void OnMouseDown(MouseEventArgs e)
    {
        base.OnMouseDown(e);
        if (e.Button == MouseButtons.Left & new Rectangle(0, 0, Width, TitleHeight).Contains(e.Location))
        {
            MouseCurrent = e.Location;
            IsDown = true;
        }
    }

    protected override void OnMouseUp(MouseEventArgs e)
    {
        base.OnMouseUp(e);
        IsDown = false;
    }

    protected override void OnMouseMove(MouseEventArgs e)
    {
        base.OnMouseMove(e);
        if (IsDown == true)
            Parent.Location = new Point(MousePosition.X - MouseCurrent.X, MousePosition.Y - MouseCurrent.Y);

    }
    #endregion

    protected override void OnCreateControl()
    {
        base.OnCreateControl();
        ParentForm.FormBorderStyle = FormBorderStyle.None;
        ParentForm.AllowTransparency = false;
        ParentForm.TransparencyKey = Color.Fuchsia;
        Dock = DockStyle.Fill;
        Invalidate();
    }

    public FlexForm()
    {
        Font = new Font("Segoe UI Light", 11.25F, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
        Padding = new Padding(1, TitleHeight, 1, 1);
    }

    protected override void OnPaint(PaintEventArgs e)
    {
        base.OnPaint(e);
        using (Graphics g = e.Graphics)
        {
            g.Clear(Color.FromArgb(246, 251, 254));

            g.FillRectangle(new SolidBrush(Color.FromArgb(254, 255, 255)), new Rectangle(0, TitleHeight, Width, Height));
            using (Pen thickness = new Pen(Drawing.tehColor))
            {
                thickness.Width = 2.0F;
                g.DrawRectangle(thickness, new Rectangle(1, 1, Width - 2, Height - 2)); //outline
            }

            using (Pen thickness = new Pen(Drawing.underlineColor))
            {
                thickness.Width = 1.5F;
                g.DrawLine(thickness, 4, TitleHeight, Width - 4, TitleHeight); //titleline
            }

            //title text
            g.DrawString(Text, Font, new SolidBrush(Color.FromArgb(220, 20, 60)), new Rectangle(7, 0, Width, TitleHeight + 2), new StringFormat
            {
                Alignment = StringAlignment.Near,
                LineAlignment = StringAlignment.Center
            });
        }
    }
}

public enum MouseState
{
    None = 0,
    Hover = 1,
    Down = 2,
    Block = 3
}

class FlexClose : Control
{
    private MouseState State = MouseState.None;

    protected override void OnMouseEnter(EventArgs e)
    {
        base.OnMouseEnter(e);
        State = MouseState.Hover;
        Invalidate();
    }
    protected override void OnMouseDown(MouseEventArgs e)
    {
        base.OnMouseDown(e);
        State = MouseState.Down;
        Invalidate();
    }
    protected override void OnMouseLeave(EventArgs e)
    {
        base.OnMouseLeave(e);
        State = MouseState.None;
        Invalidate();
    }
    protected override void OnMouseUp(MouseEventArgs e)
    {
        base.OnMouseUp(e);
        State = MouseState.Hover;
        Invalidate();
    }

    protected override void OnClick(EventArgs e)
    {
        base.OnClick(e);
        Environment.Exit(0);
    }

    protected override void OnResize(EventArgs e)
    {
        base.OnResize(e);
        Size = new Size(18, 18);
    }

    protected override void OnHandleCreated(EventArgs e)
    {
        base.OnHandleCreated(e);
        Location = new Point(FindForm().Width - 25, 8);
    }

    public FlexClose()
    {
        SetStyle(ControlStyles.AllPaintingInWmPaint | ControlStyles.UserPaint | ControlStyles.ResizeRedraw | ControlStyles.OptimizedDoubleBuffer, true);
        DoubleBuffered = true;
        BackColor = Color.FromArgb(246, 251, 254);
        Size = new Size(18, 18);
        Anchor = AnchorStyles.Top | AnchorStyles.Right;
        Font = new Font("Marlett", 12);
    }

    protected override void OnPaint(PaintEventArgs e)
    {
        Bitmap b = new Bitmap(Width, Height);
        using (Graphics g = Graphics.FromImage(b))
        {
            g.SmoothingMode = SmoothingMode.HighQuality;
            Rectangle rect = new Rectangle(0, 0, Width, Height);

            switch (State)
            {
                case MouseState.Hover:
                    {
                        g.DrawString("r", Font, new SolidBrush(Color.FromArgb(27, 136, 209)), new Rectangle(0, 0, Width, Height), new StringFormat
                        {
                            Alignment = StringAlignment.Center,
                            LineAlignment = StringAlignment.Center
                        });
                        break;
                    }
                case MouseState.None:
                    {
                        g.DrawString("r", Font, new SolidBrush(Color.FromArgb(170, 186, 198)), new Rectangle(0, 0, Width, Height), new StringFormat
                        {
                            Alignment = StringAlignment.Center,
                            LineAlignment = StringAlignment.Center
                        });
                        break;
                    }
                default:
                    g.DrawString("r", Font, new SolidBrush(Color.FromArgb(170, 186, 198)), new Rectangle(0, 0, Width, Height), new StringFormat
                    {
                        Alignment = StringAlignment.Center,
                        LineAlignment = StringAlignment.Center
                    });
                    break;
            }

            e.Graphics.DrawImage(b, 0, 0);
        }

        b.Dispose();

        base.OnPaint(e);
    }
}

class FlexMinimize : Control
{
    private MouseState State = MouseState.None;

    protected override void OnMouseEnter(EventArgs e)
    {
        base.OnMouseEnter(e);
        State = MouseState.Hover;
        Invalidate();
    }
    protected override void OnMouseDown(MouseEventArgs e)
    {
        base.OnMouseDown(e);
        State = MouseState.Down;
        Invalidate();
    }
    protected override void OnMouseLeave(EventArgs e)
    {
        base.OnMouseLeave(e);
        State = MouseState.None;
        Invalidate();
    }
    protected override void OnMouseUp(MouseEventArgs e)
    {
        base.OnMouseUp(e);
        State = MouseState.Hover;
        Invalidate();
    }

    protected override void OnClick(EventArgs e)
    {
        base.OnClick(e);
        switch (FindForm().WindowState)
        {
            case FormWindowState.Normal:
                FindForm().WindowState = FormWindowState.Minimized;
                break;
            case FormWindowState.Maximized:
                FindForm().WindowState = FormWindowState.Minimized;
                break;
        }

    }

    protected override void OnResize(EventArgs e)
    {
        base.OnResize(e);
        Size = new Size(18, 18);
    }

    protected override void OnHandleCreated(EventArgs e)
    {
        base.OnHandleCreated(e);
        Location = new Point(FindForm().Width - 43, 8);
    }

    public FlexMinimize()
    {
        SetStyle(ControlStyles.AllPaintingInWmPaint | ControlStyles.UserPaint | ControlStyles.ResizeRedraw | ControlStyles.OptimizedDoubleBuffer, true);
        DoubleBuffered = true;
        BackColor = Color.FromArgb(246, 251, 254);
        Size = new Size(18, 18);
        Anchor = AnchorStyles.Top | AnchorStyles.Right;
        Font = new Font("Marlett", 12);
    }

    protected override void OnPaint(PaintEventArgs e)
    {
        Bitmap b = new Bitmap(Width, Height);
        using (Graphics g = Graphics.FromImage(b))
        {
            g.SmoothingMode = SmoothingMode.HighQuality;
            Rectangle rect = new Rectangle(0, 0, Width, Height);

            switch (State)
            {
                case MouseState.Hover:
                    {
                        g.DrawString("0", Font, new SolidBrush(Color.FromArgb(27, 136, 209)), new Rectangle(0, 0, Width, Height), new StringFormat
                        {
                            Alignment = StringAlignment.Center,
                            LineAlignment = StringAlignment.Center
                        });
                        break;
                    }
                case MouseState.None:
                    {
                        g.DrawString("0", Font, new SolidBrush(Color.FromArgb(170, 186, 198)), new Rectangle(0, 0, Width, Height), new StringFormat
                        {
                            Alignment = StringAlignment.Center,
                            LineAlignment = StringAlignment.Center
                        });
                        break;
                    }
                default:
                    g.DrawString("0", Font, new SolidBrush(Color.FromArgb(170, 186, 198)), new Rectangle(0, 0, Width, Height), new StringFormat
                    {
                        Alignment = StringAlignment.Center,
                        LineAlignment = StringAlignment.Center
                    });
                    break;
            }

            e.Graphics.DrawImage(b, 0, 0);
        }

        b.Dispose();

        base.OnPaint(e);
    }
}

class FlexButton : Control
{
    MouseState _MouseState;
    Font _font;

    public FlexButton()
    {
        SetStyle(ControlStyles.UserPaint | ControlStyles.SupportsTransparentBackColor, true);
        DoubleBuffered = true;
        Size = new Size(145, 22);

        _MouseState = MouseState.None;
        _font = new Font("Segoe UI Light", 11.25F, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
    }

    protected override void OnPaint(PaintEventArgs e)
    {
        Bitmap b = new Bitmap(Width, Height);
        Brush _inner = new SolidBrush(Color.FromArgb(254, 255, 255));

        base.OnPaint(e);

        using (Graphics g = Graphics.FromImage(b))
        {
            switch (_MouseState)
            {
                case MouseState.Hover:
                    {
                        _inner = new SolidBrush(Color.FromArgb(140, 20, 60));
                        break;
                    };

                default:
                    _inner = new SolidBrush(Color.FromArgb(254, 255, 255));
                    break;
            }


            Drawing.DrawWithOutline(g, _inner, new SolidBrush(Color.FromArgb(220, 60, 60)), new Rectangle(0, 0, Width - 1, Height - 1));

            //button related text
            if (_MouseState == MouseState.Hover)
            {
                g.DrawString(Text, _font, new SolidBrush(Color.FromArgb(220, 20, 60)), new Rectangle(0, 1, Width, Height), new StringFormat
                {
                    Alignment = StringAlignment.Center,
                    LineAlignment = StringAlignment.Center
                });

            }
            else
            {
                g.DrawString(Text, _font, new SolidBrush(Color.FromArgb(220, 20, 60)), new Rectangle(0, 1, Width, Height), new StringFormat
                {
                    Alignment = StringAlignment.Center,
                    LineAlignment = StringAlignment.Center
                });
            }

            e.Graphics.DrawImage(b, 0, 0);
        }

        b.Dispose();
    }

    protected override void OnMouseDown(System.Windows.Forms.MouseEventArgs e)
    {
        base.OnMouseDown(e);
        _MouseState = MouseState.Down;
        Invalidate();
    }

    protected override void OnMouseUp(System.Windows.Forms.MouseEventArgs e)
    {
        base.OnMouseUp(e);
        _MouseState = MouseState.Hover;
        Invalidate();
    }

    protected override void OnMouseEnter(System.EventArgs e)
    {
        base.OnMouseEnter(e);
        _MouseState = MouseState.Hover;
        Invalidate();
    }

    protected override void OnMouseLeave(System.EventArgs e)
    {
        base.OnMouseLeave(e);
        _MouseState = MouseState.None;
        Invalidate();
    }
}

[DefaultEvent("CheckedChanged")]
class FlexCheckBox : Control
{
    Font _font;
    Font _checkFont;

    private bool _checked;
    public bool Checked
    {
        get { return _checked; }
        set
        {
            _checked = value;
            if (CheckedChanged != null)
            {
                CheckedChanged(this);
            }
            Invalidate();
        }
    }

    public FlexCheckBox()
    {
        SetStyle(ControlStyles.UserPaint | ControlStyles.SupportsTransparentBackColor | ControlStyles.OptimizedDoubleBuffer, true);
        BackColor = Color.FromArgb(254, 255, 255);
        ForeColor = Color.FromArgb(27, 136, 209);
        Size = new Size(145, 16);
        DoubleBuffered = true;

        _font = new Font("Segoe UI Light", 11.25F, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
        _checkFont = new Font("Marlett", 12, FontStyle.Regular);
        _checked = false;
    }

    public event CheckedChangedEventHandler CheckedChanged;
    public delegate void CheckedChangedEventHandler(object sender);

    protected override void OnPaint(PaintEventArgs e)
    {
        Bitmap b = new Bitmap(Width, Height);
        using (Graphics g = Graphics.FromImage(b))
        {
            g.SmoothingMode = SmoothingMode.HighQuality;
            g.CompositingQuality = CompositingQuality.HighQuality;
            g.TextRenderingHint = System.Drawing.Text.TextRenderingHint.AntiAliasGridFit;

            if (_checked)
            {
                Drawing.DrawWithOutline(g, new SolidBrush(Color.FromArgb(254, 255, 255)), new SolidBrush(Color.FromArgb(208, 230, 246)), new Rectangle(0, 0, 14, 14));
                g.DrawString("a", _checkFont, Drawing.tehColor, -3, -1);
            }
            else
            {
                Drawing.DrawWithOutline(g, new SolidBrush(Color.FromArgb(254, 255, 255)), new SolidBrush(Color.FromArgb(208, 230, 246)), new Rectangle(0, 0, 14, 14));
            }

            g.DrawString(Text, _font, new SolidBrush(ForeColor), new Point(18, 0), new StringFormat
            {
                Alignment = StringAlignment.Near,
                LineAlignment = StringAlignment.Near
            });

            e.Graphics.DrawImage(b, 0, 0);
        }

        b.Dispose();
    }

    protected override void OnClick(EventArgs e)
    {
        _checked = !_checked;
        if (CheckedChanged != null) CheckedChanged(this);

        base.OnClick(e);
        Invalidate();
    }

    protected override void OnResize(EventArgs e)
    {
        base.OnResize(e);
        Height = 16;
        Invalidate();
    }

    protected override void OnTextChanged(EventArgs e)
    {
        base.OnTextChanged(e);
        Invalidate();
    }
}

[DefaultEvent("TextChanged")]
public class FlexTextBox : Control
{
    public TextBox flexTextBox = new TextBox();

    private HorizontalAlignment _textAlignment;
    public HorizontalAlignment TextAlignment
    {
        get
        {
            return _textAlignment;
        }
        set
        {
            _textAlignment = value;
            Invalidate();
        }
    }

    private int _maxLength;
    public int MaxLength
    {
        get
        {
            return _maxLength;
        }
        set
        {
            _maxLength = value;
            flexTextBox.MaxLength = MaxLength;
            Invalidate();
        }
    }

    private bool _bPasswordMask;
    public bool UseSystemPasswordChar
    {
        get
        {
            return _bPasswordMask;
        }
        set
        {
            flexTextBox.UseSystemPasswordChar = UseSystemPasswordChar;
            _bPasswordMask = value;
            Invalidate();
        }
    }

    private bool _readOnly;
    public bool ReadOnly
    {
        get
        {
            return _readOnly;
        }
        set
        {
            _readOnly = value;
            if (flexTextBox != null)
            {
                flexTextBox.ReadOnly = value;
            }
        }
    }

    private bool _multiLine;
    public bool Multiline
    {
        get
        {
            return _multiLine;
        }
        set
        {
            _multiLine = value;
            if (flexTextBox != null)
            {
                flexTextBox.Multiline = _multiLine;
                Size = _multiLine ? new Size(Width, Height + 10) : new Size(Width, 22);
            }
        }
    }

    private void OnBaseTextChanged(object s, EventArgs e)
    {
        Text = flexTextBox.Text;
    }

    protected override void OnTextChanged(System.EventArgs e)
    {
        base.OnTextChanged(e);
        flexTextBox.Text = Text;
        Invalidate();
    }

    protected override void OnForeColorChanged(System.EventArgs e)
    {
        base.OnForeColorChanged(e);
        flexTextBox.ForeColor = ForeColor;
        Invalidate();
    }

    protected override void OnFontChanged(System.EventArgs e)
    {
        base.OnFontChanged(e);
        flexTextBox.Font = Font;
    }

    protected override void OnPaintBackground(PaintEventArgs e)
    {
        base.OnPaintBackground(e);
    }

    private void _OnKeyDown(object Obj, KeyEventArgs e)
    {
        if (e.Control && e.KeyCode == Keys.A)
        {
            flexTextBox.SelectAll();
            e.SuppressKeyPress = true;
        }
        if (e.Control && e.KeyCode == Keys.C)
        {
            flexTextBox.Copy();
            e.SuppressKeyPress = true;
        }
    }

    protected override void OnResize(System.EventArgs e)
    {
        base.OnResize(e);

        flexTextBox.Size = _multiLine ? new Size(Width - 5, Height - 5) : new Size(Width - 8, 22);
        Invalidate();
    }

    protected override void OnGotFocus(System.EventArgs e)
    {
        base.OnGotFocus(e);
        flexTextBox.Focus();
    }
    public FlexTextBox(bool is_pass)
    {
        SetStyle(ControlStyles.SupportsTransparentBackColor | ControlStyles.UserPaint, true);

        Font = new Font("Segoe UI Light", 11.25F, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
        Size = new Size(145, 22);
        DoubleBuffered = true;

        flexTextBox.Location = new Point(4, 4);
        flexTextBox.Text = String.Empty;
        flexTextBox.BorderStyle = BorderStyle.None;
        flexTextBox.Font = Font;
        flexTextBox.Size = new Size(Width - 5, 22);
        flexTextBox.BackColor = Color.FromArgb(254, 255, 255);
        flexTextBox.ForeColor = Color.FromArgb(220, 20, 60);
        flexTextBox.Multiline = false;
        flexTextBox.ScrollBars = ScrollBars.None;
        flexTextBox.KeyDown += _OnKeyDown;
        flexTextBox.TextChanged += OnBaseTextChanged;
        flexTextBox.TextAlign = TextAlignment;
        if (is_pass)
        {
            flexTextBox.UseSystemPasswordChar = true;
            flexTextBox.PasswordChar = '*';
        }

        this.Controls.Add(flexTextBox);
    }

    protected override void OnPaint(PaintEventArgs e)
    {
        Bitmap b = new Bitmap(Width, Height);
        using (Graphics g = Graphics.FromImage(b))
        {
            g.SmoothingMode = SmoothingMode.AntiAlias;
            g.Clear(Color.FromArgb(246, 251, 254));

            Drawing.DrawWithOutline(g, new SolidBrush(Color.FromArgb(254, 255, 255)), new SolidBrush(Color.FromArgb(208, 230, 246)), new Rectangle(0, 0, Width - 1, Height - 1));
            e.Graphics.DrawImage(b, 0, 0);
        }

        b.Dispose();
    }
}

public class FlexTabControl : TabControl
{
    Font _font;
    public FlexTabControl()
    {
        SetStyle(ControlStyles.AllPaintingInWmPaint | ControlStyles.OptimizedDoubleBuffer | ControlStyles.ResizeRedraw | ControlStyles.UserPaint, true);
        DoubleBuffered = true;
        SizeMode = TabSizeMode.Fixed;
        ItemSize = new Size(120, 20);
        _font = new Font("Segoe UI Light", 11.25F, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point, ((byte)(0))); 
    }

    protected override void CreateHandle()
    {
        base.CreateHandle();
        Alignment = TabAlignment.Top;

        foreach (TabPage tp in TabPages)
            tp.BackColor = Color.FromArgb(254, 255, 255);
    }

    protected override void OnPaint(PaintEventArgs e)
    {
        Bitmap b = new Bitmap(Width, Height);
        using (Graphics g = Graphics.FromImage(b))
        {
            g.Clear(Color.FromArgb(254, 255, 255));

            for (int i = 0; i <= TabCount - 1; i++)
            {
                Rectangle TabRectangle = GetTabRect(i);

                //fix tab position
                TabRectangle.X += 2;
                //TabRectangle.Height = 34;
                TabRectangle.Y += 1;

                Rectangle stringRect = TabRectangle;
                stringRect.X += 3;

                if (i == SelectedIndex)
                {
                    g.FillRectangle(Drawing.tehColor, TabRectangle);
                    g.DrawString(TabPages[i].Text, _font, Brushes.White, stringRect, new StringFormat
                    {
                        LineAlignment = StringAlignment.Center,
                        Alignment = StringAlignment.Near
                    });
                }
                else
                {
                    g.DrawRectangle(new Pen(Color.FromArgb(208, 230, 246)), TabRectangle);
                    g.DrawString(TabPages[i].Text, _font, Drawing.tehColor, stringRect, new StringFormat
                    {
                        LineAlignment = StringAlignment.Center,
                        Alignment = StringAlignment.Near
                    });
                }

                g.DrawLine(new Pen(Drawing.tehColor), new Point(TabRectangle.X, TabRectangle.Y + TabRectangle.Height),
                    new Point(this.Width - 5, TabRectangle.Y + TabRectangle.Height));
            }

            e.Graphics.DrawImage(b, 0, 0);
        }

        b.Dispose();
        base.OnPaint(e);
    }
}