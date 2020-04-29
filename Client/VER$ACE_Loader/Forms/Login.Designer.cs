using System.Drawing;
using System.Drawing.Drawing2D;
using System.Drawing;
using System.ComponentModel;
using System;
using System.Windows.Forms;


namespace versace_loader
{
    partial class Login
    {
        /// <summary>
        /// Required designer variable.
        /// </summary>
        private System.ComponentModel.IContainer components = null;

        /// <summary>
        /// Clean up any resources being used.
        /// </summary>
        /// <param name="disposing">true if managed resources should be disposed; otherwise, false.</param>
        protected override void Dispose(bool disposing)
        {
            if (disposing && (components != null))
            {
                components.Dispose();
            }
            base.Dispose(disposing);
        }

        #region Windows Form Designer generated code

        /// <summary>
        /// Required method for Designer support - do not modify
        /// the contents of this method with the code editor.
        /// </summary>
        private void InitializeComponent()
        {
            this.flexForm1 = new FlexForm();
            this.flex_login_button = new FlexButton();
            this.flex_remember_me = new FlexCheckBox();
            this.flexClose1 = new FlexClose();
            this.flex_password_box = new FlexTextBox(true);
            this.flex_username_box = new FlexTextBox(false);
            this.flexForm1.SuspendLayout();
            this.SuspendLayout();
            // 
            // flexForm1
            // 
            this.flexForm1.Controls.Add(this.flex_login_button);
            this.flexForm1.Controls.Add(this.flex_remember_me);
            this.flexForm1.Controls.Add(this.flex_password_box);
            this.flexForm1.Controls.Add(this.flexClose1);
            this.flexForm1.Controls.Add(this.flex_username_box);
            this.flexForm1.Dock = System.Windows.Forms.DockStyle.Fill;
            this.flexForm1.Font = new System.Drawing.Font("Segoe UI Light", 11.25F, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.flexForm1.Location = new System.Drawing.Point(0, 0);
            this.flexForm1.Name = "flexForm1";
            this.flexForm1.Padding = new System.Windows.Forms.Padding(1, 32, 1, 1);
            this.flexForm1.Size = new System.Drawing.Size(300, 150);
            this.flexForm1.TabIndex = 0;
            this.flexForm1.Text = "VER$ACE - [[USERNAME]]";
            // 
            // flex_login_button
            // 
            this.flex_login_button.ForeColor = System.Drawing.Color.Crimson;
            this.flex_login_button.Location = new System.Drawing.Point(75, 113);
            this.flex_login_button.Name = "flex_login_button";
            this.flex_login_button.Size = new System.Drawing.Size(145, 22);
            this.flex_login_button.TabIndex = 0;
            this.flex_login_button.Text = "Login";
            this.flex_login_button.Click += new System.EventHandler(this.btnLogin_Click);
            // 
            // flex_remember_me
            // 
            this.flex_remember_me.BackColor = System.Drawing.Color.White;
            this.flex_remember_me.Checked = false;
            this.flex_remember_me.ForeColor = System.Drawing.Color.Crimson;
            this.flex_remember_me.Location = new System.Drawing.Point(75, 91);
            this.flex_remember_me.Name = "flex_remember_me";
            this.flex_remember_me.Size = new System.Drawing.Size(145, 16);
            this.flex_remember_me.TabIndex = 3;
            this.flex_remember_me.Text = "Remember Me";
            // 
            // flex_password_box
            // 
            this.flex_password_box.Font = new System.Drawing.Font("Segoe UI Light", 11.25F, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.flex_password_box.Location = new System.Drawing.Point(75, 63);
            this.flex_password_box.MaxLength = 0;
            this.flex_password_box.Multiline = false;
            this.flex_password_box.Name = "flex_password_box";
            this.flex_password_box.ReadOnly = false;
            this.flex_password_box.Size = new System.Drawing.Size(145, 22);
            this.flex_password_box.TabIndex = 2;
            this.flex_password_box.Text = "Password";
            this.flex_password_box.TextAlignment = System.Windows.Forms.HorizontalAlignment.Left;
            this.flex_password_box.UseSystemPasswordChar = true;
            // 
            // flexClose1
            // 
            this.flexClose1.Anchor = ((System.Windows.Forms.AnchorStyles)((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Right)));
            this.flexClose1.BackColor = System.Drawing.Color.White;
            this.flexClose1.Font = new System.Drawing.Font("Marlett", 12F);
            this.flexClose1.Location = new System.Drawing.Point(291, 8);
            this.flexClose1.Name = "flexClose1";
            this.flexClose1.Size = new System.Drawing.Size(18, 18);
            this.flexClose1.TabIndex = 0;
            this.flexClose1.Text = "X";
            // 
            // flex_username_box
            // 
            this.flex_username_box.Font = new System.Drawing.Font("Segoe UI Light", 11.25F, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.flex_username_box.Location = new System.Drawing.Point(75, 35);
            this.flex_username_box.MaxLength = 0;
            this.flex_username_box.Multiline = false;
            this.flex_username_box.Name = "flex_username_box";
            this.flex_username_box.ReadOnly = false;
            this.flex_username_box.Size = new System.Drawing.Size(145, 22);
            this.flex_username_box.TabIndex = 1;
            this.flex_username_box.Text = "Username";
            this.flex_username_box.TextAlignment = System.Windows.Forms.HorizontalAlignment.Left;
            this.flex_username_box.UseSystemPasswordChar = false;
            // 
            // Login
            // 
            this.AutoScaleDimensions = new System.Drawing.SizeF(6F, 13F);
            this.AutoScaleMode = System.Windows.Forms.AutoScaleMode.Font;
            this.ClientSize = new System.Drawing.Size(300, 150);
            this.Controls.Add(this.flexForm1);
            this.FormBorderStyle = System.Windows.Forms.FormBorderStyle.None;
            this.Name = "Login";
            this.Text = "Form1";
            this.TransparencyKey = System.Drawing.Color.Fuchsia;
            this.flexForm1.ResumeLayout(false);
            this.ResumeLayout(false);

        }

        #endregion

        private FlexForm flexForm1;
        private FlexClose flexClose1;
        private FlexTextBox flex_password_box;
        private FlexTextBox flex_username_box;
        private FlexButton flex_login_button;
        private FlexCheckBox flex_remember_me;
    }
}

