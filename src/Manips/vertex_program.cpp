////////////////////////////////////////////////////////
//
// GEM - Graphics Environment for Multimedia
//
// Created by tigital on 10/16/04.
// Copyright 2004 James Tittle
//
// Implementation file
//
//    Copyright (c) 1997-1999 Mark Danks.
//    Copyright (c) G�nther Geiger.
//    Copyright (c) 2001-2002 IOhannes m zmoelnig. forum::f�r::uml�ute. IEM
//    Copyright (c) 2002 James Tittle & Chris Clepper
//    For information on usage and redistribution, and for a DISCLAIMER OF ALL
//    WARRANTIES, see the file, "GEM.LICENSE.TERMS" in this distribution.
//
/////////////////////////////////////////////////////////

#include "vertex_program.h"

#include <string.h>
#include <stdio.h>
#include <unistd.h>

#ifdef __APPLE__
#include <AGL/agl.h>
extern bool HaveValidContext (void);
#endif

CPPEXTERN_NEW_WITH_ONE_ARG(vertex_program, t_symbol *, A_DEFSYM)

/////////////////////////////////////////////////////////
//
// vertex_program
//
/////////////////////////////////////////////////////////
// Constructor
//
/////////////////////////////////////////////////////////
vertex_program :: vertex_program() :
  m_programType(GEM_PROGRAM_none), 
  m_programID(0), 
  m_programString(NULL), m_size(0),
  m_envNum(-1)
{
}
vertex_program :: vertex_program(t_symbol *filename) :
  m_programType(GEM_PROGRAM_none), 
  m_programID(0), 
  m_programString(NULL), m_size(0)
{
  openMess(filename);
}

/////////////////////////////////////////////////////////
// Destructor
//
/////////////////////////////////////////////////////////
vertex_program :: ~vertex_program()
{
  closeMess();
}


/////////////////////////////////////////////////////////
// closeMess
//
/////////////////////////////////////////////////////////
void vertex_program :: closeMess(void)
{
  delete [] m_programString;
  m_programString=NULL;
  m_size=0;
  if(m_programID){
    switch(m_programType){
#ifdef GL_NV_vertex_program
    case(GEM_PROGRAM_NV):
      glDeleteProgramsNV(1,&m_programID);
      break;
#endif
#ifdef GL_ARB_vertex_program
    case(GEM_PROGRAM_ARB):
      glDeleteProgramsARB(1,&m_programID);
      break;
#endif
    default:
      break;
    }
  }

  m_programID=0;
  m_programType=GEM_PROGRAM_none;
}
/////////////////////////////////////////////////////////
// openMess
//
/////////////////////////////////////////////////////////
GLint vertex_program :: queryProgramtype(char*program)
{
#ifdef GL_VERTEX_PROGRAM_ARB
  if(!strncmp(program,"!!ARBvp1.0",10)){
    m_programTarget=GL_VERTEX_PROGRAM_ARB;
    return(GEM_PROGRAM_ARB);
  }
#endif /* GL_VERTEX_PROGRAM_ARB */
#ifdef GL_VERTEX_PROGRAM_NV
  if(!strncmp(program,"!!VP1.0",7)){
    m_programTarget=GL_VERTEX_PROGRAM_NV;
    return(GEM_PROGRAM_NV);
  }
#endif /* GL_VERTEX_PROGRAM_NV */

  return GEM_PROGRAM_none;
}


void vertex_program :: openMess(t_symbol *filename)
{
  char buf2[MAXPDSTRING];
  char *bufptr=NULL;

  if(NULL==filename || NULL==filename->s_name || &s_==filename || 0==*filename->s_name)return;
#ifdef __APPLE__
  if (!HaveValidContext ()) {
    post("GEM: [%s] - need window/context to load program", m_objectname->s_name);
    return;
  }
#endif

  // Clean up any open files
  closeMess();

  int fd=-1;
  if ((fd=open_via_path(canvas_getdir(getCanvas())->s_name, filename->s_name, "", 
                        buf2, &bufptr, MAXPDSTRING, 1))>=0){
    close(fd);
    sprintf(m_buf, "%s/%s", buf2, bufptr);
  } else
    canvas_makefilename(getCanvas(), filename->s_name, m_buf, MAXPDSTRING);

  FILE *file = fopen(m_buf,"r");
  if(file) {
    fseek(file,0,SEEK_END);
    int size = ftell(file);
    m_programString = new char[size + 1];
    memset(m_programString,0,size + 1);
    fseek(file,0,SEEK_SET);
    fread(m_programString,1,size,file);
    fclose(file);
  } else {
    m_programString = new char[strlen(m_buf) + 1];
    strcpy(m_programString,m_buf);
  }
  m_size=strlen(m_programString);
  m_programType=queryProgramtype(m_programString);
  if(m_programType==GEM_PROGRAM_none){
    m_programID = 0;
    char *s = m_programString;
    while(*s && *s != '\n') s++;
    *s = '\0';
    post("[%s]: unknown program header \"%s\" or error open \"%s\" file\n",
	 m_objectname->s_name,
	 m_programString,filename->s_name);
    
    delete m_programString; m_programString=NULL;
    m_size=0;
    return;
  }

  post("[%s]: Loaded file: %s\n", m_objectname->s_name, m_buf);
}

/////////////////////////////////////////////////////////
// render
//
/////////////////////////////////////////////////////////
void vertex_program :: LoadProgram(void)
{
  if(NULL==m_programString)return;
  GLint error=-1;

  switch(m_programType){
#ifdef GL_NV_vertex_program
  case  GEM_PROGRAM_NV:
    if (m_programID==0)
      {
	glEnable(m_programTarget);
	glGenProgramsNV(1, &m_programID);
	glBindProgramNV(m_programTarget, m_programID);
	glLoadProgramNV(m_programTarget, m_programID, m_size, (GLubyte*)m_programString);
	glGetIntegerv(GL_PROGRAM_ERROR_POSITION_NV, &error);
      } else {
        glEnable(m_programTarget);
	glBindProgramNV(m_programTarget, m_programID);
	return;
    }
    break;
#endif /* GL_NV_vertex_program */
#ifdef GL_ARB_vertex_program
  case  GEM_PROGRAM_ARB:
    if (m_programID==0)
      {
	glEnable(m_programTarget);
	glGenProgramsARB(1, &m_programID);
	glBindProgramARB( m_programTarget, m_programID);
	glProgramStringARB( m_programTarget, GL_PROGRAM_FORMAT_ASCII_ARB, m_size, m_programString);
        glGetIntegerv(GL_PROGRAM_ERROR_POSITION_ARB, &error);
      } else {
        glEnable(m_programTarget);
	glBindProgramARB(m_programTarget, m_programID);
	return;
    }
    break;
#endif /* GL_ARB_vertex_program */
  default:
    return;
  }

  if(error != -1) {
    int line = 0;
    char *s = m_programString;
    while(error-- && *s) if(*s++ == '\n') line++;
    while(s >= m_programString && *s != '\n') s--;
    char *e = ++s;
    while(*e != '\n' && *e != '\0') e++;
    *e = '\0';
    post("[%s]:  program error at line %d:\n\"%s\"\n",m_objectname->s_name,line,s);
#ifdef GL_PROGRAM_ERROR_STRING_ARB
    post("[%s]:  %s\n", m_objectname->s_name, glGetString(GL_PROGRAM_ERROR_STRING_ARB));
#endif /* GL_PROGRAM_ERROR_STRING_ARB */
  }

#if defined GL_ARB_vertex_program && defined GL_PROGRAM_UNDER_NATIVE_LIMITS_ARB
  GLint isUnderNativeLimits;
  glGetProgramivARB( m_programTarget, GL_PROGRAM_UNDER_NATIVE_LIMITS_ARB, &isUnderNativeLimits);
  
// If the program is over the hardware's limits, print out some information
  if (isUnderNativeLimits!=1)
  {
		// Go through the most common limits that are exceeded
    post("[%s]:  is beyond hardware limits", m_objectname->s_name);

		GLint aluInstructions, maxAluInstructions;
		glGetProgramivARB(m_programTarget, GL_PROGRAM_ALU_INSTRUCTIONS_ARB, &aluInstructions);
		glGetProgramivARB(m_programTarget, GL_MAX_PROGRAM_ALU_INSTRUCTIONS_ARB, &maxAluInstructions);
		if (aluInstructions>maxAluInstructions)
			post("[%s]: Compiles to too many ALU instructions (%d, limit is %d)\n", m_buf, aluInstructions, maxAluInstructions);

		GLint textureInstructions, maxTextureInstructions;
		glGetProgramivARB(m_programTarget, GL_PROGRAM_TEX_INSTRUCTIONS_ARB, &textureInstructions);
		glGetProgramivARB(m_programTarget, GL_MAX_PROGRAM_TEX_INSTRUCTIONS_ARB, &maxTextureInstructions);
		if (textureInstructions>maxTextureInstructions)
			post("[%s]: Compiles to too many texture instructions (%d, limit is %d)\n", m_buf, textureInstructions, maxTextureInstructions);

		GLint textureIndirections, maxTextureIndirections;
		glGetProgramivARB(m_programTarget, GL_PROGRAM_TEX_INDIRECTIONS_ARB, &textureIndirections);
		glGetProgramivARB(m_programTarget, GL_MAX_PROGRAM_TEX_INDIRECTIONS_ARB, &maxTextureIndirections);
		if (textureIndirections>maxTextureIndirections)
			post("[%s]: Compiles to too many texture indirections (%d, limit is %d)\n", m_buf, textureIndirections, maxTextureIndirections);

		GLint nativeTextureIndirections, maxNativeTextureIndirections;
		glGetProgramivARB(m_programTarget, GL_PROGRAM_NATIVE_TEX_INDIRECTIONS_ARB, &nativeTextureIndirections);
		glGetProgramivARB(m_programTarget, GL_MAX_PROGRAM_NATIVE_TEX_INDIRECTIONS_ARB, &maxNativeTextureIndirections);
		if (nativeTextureIndirections>maxNativeTextureIndirections)
			post("[%s]: Compiles to too many native texture indirections (%d, limit is %d)\n", m_buf, nativeTextureIndirections, maxNativeTextureIndirections);

		GLint nativeAluInstructions, maxNativeAluInstructions;
		glGetProgramivARB(m_programTarget, GL_PROGRAM_NATIVE_ALU_INSTRUCTIONS_ARB, &nativeAluInstructions);
		glGetProgramivARB(m_programTarget, GL_MAX_PROGRAM_NATIVE_ALU_INSTRUCTIONS_ARB, &maxNativeAluInstructions);
		if (nativeAluInstructions>maxNativeAluInstructions)
			post("[%s]: Compiles to too many native ALU instructions (%d, limit is %d)\n", m_buf, nativeAluInstructions, maxNativeAluInstructions);
  }
#endif
}


void vertex_program :: startRendering()
{
  if (m_programString == NULL)
    {
      error("[%s]: need to load a program", m_objectname->s_name);
      return;
    }

  LoadProgram();
}

void vertex_program :: render(GemState *state)
{
#if defined GL_ARB_vertex_program || defined GL_NV_vertex_program
  LoadProgram();
  if(m_programID&&(m_envNum>=0)){
    glProgramEnvParameter4fvARB(m_programTarget, m_envNum, m_param);
  }
#endif
} 

/////////////////////////////////////////////////////////
// postrender
//
/////////////////////////////////////////////////////////
void vertex_program :: postrender(GemState *state)
{
  if(m_programID){
    switch(m_programType){
    case  GEM_PROGRAM_NV:  case  GEM_PROGRAM_ARB:
      glDisable( m_programTarget );
      break;
    default:
      break;
    }
  }
}

/////////////////////////////////////////////////////////
// printInfo
//
/////////////////////////////////////////////////////////
void vertex_program :: printInfo()
{
#ifdef __APPLE__
	if (!HaveValidContext ()) {
		post("GEM: vertex_program - need window/context to load program");
		return;
	}
#endif
#ifdef GL_ARB_vertex_program
	GLint bitnum = 0;
	post("Vertex_Program Hardware Info");
	post("============================");
	glGetIntegerv( GL_MAX_VERTEX_ATTRIBS_ARB, &bitnum );
	post("MAX_VERTEX_ATTRIBS: %d", bitnum);
	glGetIntegerv( GL_MAX_PROGRAM_MATRICES_ARB, &bitnum );
	post("MAX_PROGRAM_MATRICES: %d", bitnum);
	glGetIntegerv( GL_MAX_PROGRAM_MATRIX_STACK_DEPTH_ARB, &bitnum );
	post("MAX_PROGRAM_MATRIX_STACK_DEPTH: %d", bitnum);
	
	glGetProgramivARB( GL_VERTEX_PROGRAM_ARB, GL_MAX_PROGRAM_INSTRUCTIONS_ARB, &bitnum);
	post("MAX_PROGRAM_INSTRUCTIONS: %d", bitnum);
	glGetProgramivARB( GL_VERTEX_PROGRAM_ARB, GL_MAX_PROGRAM_NATIVE_INSTRUCTIONS_ARB, &bitnum);
	post("MAX_PROGRAM_NATIVE_INSTRUCTIONS: %d", bitnum);
	glGetProgramivARB( GL_VERTEX_PROGRAM_ARB, GL_MAX_PROGRAM_TEMPORARIES_ARB, &bitnum);
	post("MAX_PROGRAM_TEMPORARIES: %d", bitnum);
	glGetProgramivARB( GL_VERTEX_PROGRAM_ARB, GL_MAX_PROGRAM_NATIVE_TEMPORARIES_ARB, &bitnum);
	post("MAX_PROGRAM_NATIVE_TEMPORARIES: %d", bitnum);
	glGetProgramivARB( GL_VERTEX_PROGRAM_ARB, GL_MAX_PROGRAM_PARAMETERS_ARB, &bitnum);
	post("MAX_PROGRAM_PARAMETERS: %d", bitnum);
	glGetProgramivARB( GL_VERTEX_PROGRAM_ARB, GL_MAX_PROGRAM_NATIVE_PARAMETERS_ARB, &bitnum);
	post("MAX_PROGRAM_NATIVE_PARAMETERS: %d", bitnum);
	glGetProgramivARB( GL_VERTEX_PROGRAM_ARB, GL_MAX_PROGRAM_ATTRIBS_ARB, &bitnum);
	post("MAX_PROGRAM_ATTRIBS: %d", bitnum);
	glGetProgramivARB( GL_VERTEX_PROGRAM_ARB, GL_MAX_PROGRAM_NATIVE_ATTRIBS_ARB, &bitnum);
	post("MAX_PROGRAM_NATIVE_ATTRIBS: %d", bitnum);
	glGetProgramivARB( GL_VERTEX_PROGRAM_ARB, GL_MAX_PROGRAM_ADDRESS_REGISTERS_ARB, &bitnum);
	post("MAX_PROGRAM_ADDRESS_REGISTERS: %d", bitnum);
	glGetProgramivARB( GL_VERTEX_PROGRAM_ARB, GL_MAX_PROGRAM_NATIVE_ADDRESS_REGISTERS_ARB, &bitnum);
	post("MAX_PROGRAM_NATIVE_ADDRESS_REGISTERS: %d", bitnum);
	glGetProgramivARB( GL_VERTEX_PROGRAM_ARB, GL_MAX_PROGRAM_LOCAL_PARAMETERS_ARB, &bitnum);
	post("MAX_PROGRAM_LOCAL_PARAMETERS: %d", bitnum);
	glGetProgramivARB( GL_VERTEX_PROGRAM_ARB, GL_MAX_PROGRAM_ENV_PARAMETERS_ARB, &bitnum);
	post("MAX_PROGRAM_ENV_PARAMETERS: %d", bitnum);
	post("");
	
	glGetProgramivARB( GL_VERTEX_PROGRAM_ARB, GL_PROGRAM_INSTRUCTIONS_ARB, &bitnum);
	post("PROGRAM_INSTRUCTIONS: %d", bitnum);
	glGetProgramivARB( GL_VERTEX_PROGRAM_ARB, GL_PROGRAM_NATIVE_INSTRUCTIONS_ARB, &bitnum);
	post("PROGRAM_NATIVE_INSTRUCTIONS: %d", bitnum);
	glGetProgramivARB( GL_VERTEX_PROGRAM_ARB, GL_PROGRAM_TEMPORARIES_ARB, &bitnum);
	post("PROGRAM_TEMPORARIES: %d", bitnum);
	glGetProgramivARB( GL_VERTEX_PROGRAM_ARB, GL_PROGRAM_NATIVE_TEMPORARIES_ARB, &bitnum);
	post("PROGRAM_NATIVE_TEMPORARIES: %d", bitnum);
	glGetProgramivARB( GL_VERTEX_PROGRAM_ARB, GL_PROGRAM_PARAMETERS_ARB, &bitnum);
	post("PROGRAM_PARAMETERS: %d", bitnum);
	glGetProgramivARB( GL_VERTEX_PROGRAM_ARB, GL_PROGRAM_NATIVE_PARAMETERS_ARB, &bitnum);
	post("PROGRAM_NATIVE_PARAMETERS: %d", bitnum);
	glGetProgramivARB( GL_VERTEX_PROGRAM_ARB, GL_PROGRAM_ATTRIBS_ARB, &bitnum);
	post("PROGRAM_ATTRIBS: %d", bitnum);
	glGetProgramivARB( GL_VERTEX_PROGRAM_ARB, GL_PROGRAM_NATIVE_ATTRIBS_ARB, &bitnum);
	post("PROGRAM_NATIVE_ATTRIBS: %d", bitnum);
	glGetProgramivARB( GL_VERTEX_PROGRAM_ARB, GL_PROGRAM_ADDRESS_REGISTERS_ARB, &bitnum);
	post("PROGRAM_ADDRESS_REGISTERS: %d", bitnum);
	glGetProgramivARB( GL_VERTEX_PROGRAM_ARB, GL_PROGRAM_NATIVE_ADDRESS_REGISTERS_ARB, &bitnum);
	post("PROGRAM_NATIVE_ADDRESS_REGISTERS: %d", bitnum);
#endif /* GL_ARB_vertex_program */
}

/////////////////////////////////////////////////////////
// static member function
//
/////////////////////////////////////////////////////////
void vertex_program :: obj_setupCallback(t_class *classPtr)
{
  class_addmethod(classPtr, (t_method)&vertex_program::openMessCallback,
		  gensym("open"), A_SYMBOL, A_NULL);
  class_addmethod(classPtr, (t_method)&vertex_program::printMessCallback,
		  gensym("print"), A_NULL);
}
void vertex_program :: openMessCallback(void *data, t_symbol *filename)
{
	    GetMyClass(data)->openMess(filename);
}
void vertex_program :: printMessCallback(void *data)
{
	GetMyClass(data)->printInfo();
}
